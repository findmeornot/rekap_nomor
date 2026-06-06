<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $filters = $this->resolveDateFilter($request);
        $uiFilters = $this->resolveUiFilters($request);
        $selectedSubLeaderId = $request->integer('sub_leader_id');
        $subLeaders = User::query()
            ->where('role', User::ROLE_SUB_LEADER)
            ->when(
                $user->team_id,
                fn (Builder $query) => $query->where('team_id', $user->team_id),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->withCount([
                'contactsEntered as contacts_entered_count' => function (Builder $query) use ($user, $filters, $uiFilters): void {
                    $this->applyLeaderContactScope($query, $user);
                    $this->applyDateFilter($query, $filters);
                    $this->applyListFilters($query, $uiFilters);
                },
            ])
            ->orderBy('name')
            ->get();

        $allowedSubLeaderIds = $subLeaders->pluck('id')->all();
        if ($selectedSubLeaderId > 0 && ! in_array($selectedSubLeaderId, $allowedSubLeaderIds, true)) {
            $selectedSubLeaderId = null;
        }

        $contactsQuery = $this->scopedContacts($user)
            ->with('subLeader:id,name')
            ->latest();
        $this->applyDateFilter($contactsQuery, $filters);
        $this->applyListFilters($contactsQuery, $uiFilters);

        if ($selectedSubLeaderId) {
            $contactsQuery->where('sub_leader_id', $selectedSubLeaderId);
        }

        $perPage = (int) $uiFilters['per_page'];

        $totalContactsCount = $this->scopedContacts($user)->count();
        $totalContactedCount = $this->scopedContacts($user)
            ->where('is_contacted', true)
            ->count();
        $contactedThisMonthCount = $this->scopedContacts($user)
            ->where('is_contacted', true)
            ->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereYear('status_updated_at', now()->year)
                        ->whereMonth('status_updated_at', now()->month);
                })->orWhere(function (Builder $query) {
                    $query->whereNull('status_updated_at')
                        ->whereYear('contacted_at', now()->year)
                        ->whereMonth('contacted_at', now()->month);
                });
            })
            ->count();

        $monthlyContactedData = $this->scopedContacts($user)
            ->where('is_contacted', true)
            ->where(function (Builder $query) {
                $query->whereNotNull('status_updated_at')
                    ->orWhereNotNull('contacted_at');
            })
            ->get(['status_updated_at', 'contacted_at'])
            ->groupBy(fn ($contact) => ($contact->status_updated_at ?? $contact->contacted_at)->format('Y-m'))
            ->sortKeys()
            ->map(fn ($contacts, $key) => [
                'label' => Carbon::createFromFormat('Y-m', $key)->format('M Y'),
                'count' => $contacts->count(),
            ])
            ->values();

        $personalHandledCount = $user->team_id
            ? Contact::query()
                ->where('team_id', $user->team_id)
                ->where('is_contacted', true)
                ->where('status_updated_by', $user->id)
                ->count()
            : 0;

        $target = User::TARGET_LEADER;
        $progress = $target > 0 ? (int) round(($personalHandledCount / $target) * 100) : 0;

        return view('leader.contacts.index', [
            'subLeaders' => $subLeaders,
            'selectedSubLeaderId' => $selectedSubLeaderId,
            'filters' => $filters,
            'uiFilters' => $uiFilters,
            'contacts' => $contactsQuery->paginate($perPage)->withQueryString(),
            'totalContactsCount' => $totalContactsCount,
            'totalContactedCount' => $totalContactedCount,
            'contactedThisMonthCount' => $contactedThisMonthCount,
            'monthlyContactedData' => $monthlyContactedData,
            'target' => $target,
            'progress' => $progress,
            'personalHandledCount' => $personalHandledCount,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $filters = $this->resolveDateFilter($request);
        $uiFilters = $this->resolveUiFilters($request);
        $selectedSubLeaderId = $request->integer('sub_leader_id');
        $allowedSubLeaderIds = User::query()
            ->where('role', User::ROLE_SUB_LEADER)
            ->when(
                $user->team_id,
                fn (Builder $query) => $query->where('team_id', $user->team_id),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->pluck('id')
            ->all();
        if ($selectedSubLeaderId > 0 && ! in_array($selectedSubLeaderId, $allowedSubLeaderIds, true)) {
            $selectedSubLeaderId = null;
        }

        $fileName = 'rekap-kontak-leader-'.$user->id.'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($user, $selectedSubLeaderId, $filters, $uiFilters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama Kontak', 'Nomor', 'Asisten Marketing', 'Status', 'Tanggal Input']);

            $contactsQuery = $this->scopedContacts($user)
                ->with('subLeader:id,name')
                ->orderByDesc('created_at');

            $this->applyDateFilter($contactsQuery, $filters);
            $this->applyListFilters($contactsQuery, $uiFilters);

            if ($selectedSubLeaderId) {
                $contactsQuery->where('sub_leader_id', $selectedSubLeaderId);
            }

            $contactsQuery->chunk(200, function ($contacts) use ($handle) {
                    foreach ($contacts as $contact) {
                        fputcsv($handle, [
                            $contact->contact_name ?? '-',
                            $contact->phone,
                            $contact->subLeader?->name ?? '-',
                            $contact->statusLabel(),
                            $contact->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function whatsapp(Contact $contact): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->leaderCanAccessContact($user, $contact), 404);

        // Do not change contact status when opening WhatsApp — checkbox is the only source of truth.
        return redirect()->away($contact->whatsapp_url);
    }

    public function updateStatus(Contact $contact, Request $request): JsonResponse
    {
        $user = auth()->user();

        abort_unless($this->leaderCanAccessContact($user, $contact), 404);

        $validated = $request->validate([
            'is_contacted' => ['required', 'boolean'],
        ]);

        $contact->setIsContacted($user, (bool) $validated['is_contacted']);

        return response()->json([
            'ok' => true,
            'is_contacted' => (bool) $validated['is_contacted'],
            'label' => $contact->fresh()->statusLabel(),
        ]);
    }

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'distinct'],
        ]);

        $ids = array_map('intval', $validated['contact_ids']);

        // Only allow updating contacts within the leader's team
        $updated = Contact::query()
            ->whereIn('id', $ids)
            ->where('team_id', $user->team_id)
            ->update([
                'is_contacted' => true,
                'status' => Contact::STATUS_CONTACTED,
                'status_updated_by' => $user->id,
                'status_updated_at' => now(),
                'contacted_at' => now(),
                'contacted_by_leader_id' => $user->id,
            ]);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    /**
     * @return Builder<Contact>
     */
    private function scopedContacts(User $user): Builder
    {
        $query = Contact::query();
        $this->applyLeaderContactScope($query, $user);

        return $query;
    }

    /**
     * @param Builder<Contact> $query
     */
    private function applyLeaderContactScope(Builder $query, User $user): void
    {
        if (! $user->team_id) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('team_id', $user->team_id);
    }

    private function leaderCanAccessContact(User $user, Contact $contact): bool
    {
        if (! $user->team_id || ! $contact->team_id) {
            return false;
        }

        return (int) $contact->team_id === (int) $user->team_id;
    }

    /**
     * @return array{period: string, start_date: string|null, end_date: string|null}
     */
    private function resolveDateFilter(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:all,7d,30d,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return [
            'period' => (string) ($validated['period'] ?? 'all'),
            'start_date' => isset($validated['start_date']) ? (string) $validated['start_date'] : null,
            'end_date' => isset($validated['end_date']) ? (string) $validated['end_date'] : null,
        ];
    }

    /**
     * @param Builder<Contact> $query
     * @param array{period: string, start_date: string|null, end_date: string|null} $filters
     */
    private function applyDateFilter(Builder $query, array $filters): void
    {
        $period = $filters['period'];

        if ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(6)->startOfDay());
            return;
        }

        if ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(29)->startOfDay());
            return;
        }

        if ($period === 'custom') {
            $startDate = $filters['start_date']
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : null;
            $endDate = $filters['end_date']
                ? Carbon::parse($filters['end_date'])->endOfDay()
                : null;

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
                return;
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        }
    }

    /**
     * @return array{q: string|null, status: string, per_page: int}
     */
    private function resolveUiFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,contacted,uncontacted'],
            'per_page' => ['nullable', 'in:10,20,50,100'],
        ]);

        return [
            'q' => isset($validated['q']) ? trim((string) $validated['q']) : null,
            'status' => (string) ($validated['status'] ?? 'all'),
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }

    /**
     * @param Builder<Contact> $query
     * @param array{q: string|null, status: string, per_page: int} $uiFilters
     */
    private function applyListFilters(Builder $query, array $uiFilters): void
    {
        if ($uiFilters['q']) {
            $keyword = $uiFilters['q'];
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder
                    ->where('contact_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhereHas('subLeader', fn (Builder $subLeaderQuery) => $subLeaderQuery->where('name', 'like', "%{$keyword}%"));
            });
        }

        if ($uiFilters['status'] === 'contacted') {
            $query->where('is_contacted', true);
        }

        if ($uiFilters['status'] === 'uncontacted') {
            $query->where('is_contacted', false);
        }
    }
}
