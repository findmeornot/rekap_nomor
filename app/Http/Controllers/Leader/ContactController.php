<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        $subLeaders = $user->subLeaders()
            ->withCount([
                'contactsEntered as contacts_entered_count' => function (Builder $query) use ($user, $filters, $uiFilters): void {
                    $query->where('leader_id', $user->id);
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

        $contactsQuery = Contact::where('leader_id', $user->id)
            ->with('subLeader:id,name')
            ->latest();
        $this->applyDateFilter($contactsQuery, $filters);
        $this->applyListFilters($contactsQuery, $uiFilters);

        if ($selectedSubLeaderId) {
            $contactsQuery->where('sub_leader_id', $selectedSubLeaderId);
        }

        $perPage = (int) $uiFilters['per_page'];

        return view('leader.contacts.index', [
            'subLeaders' => $subLeaders,
            'selectedSubLeaderId' => $selectedSubLeaderId,
            'filters' => $filters,
            'uiFilters' => $uiFilters,
            'contacts' => $contactsQuery->paginate($perPage)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $filters = $this->resolveDateFilter($request);
        $uiFilters = $this->resolveUiFilters($request);
        $selectedSubLeaderId = $request->integer('sub_leader_id');
        $allowedSubLeaderIds = $user->subLeaders()->pluck('id')->all();
        if ($selectedSubLeaderId > 0 && ! in_array($selectedSubLeaderId, $allowedSubLeaderIds, true)) {
            $selectedSubLeaderId = null;
        }

        $fileName = 'rekap-kontak-leader-'.$user->id.'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($user, $selectedSubLeaderId, $filters, $uiFilters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama Kontak', 'Nomor', 'Sub Leader', 'Status', 'Tanggal Input']);

            $contactsQuery = Contact::where('leader_id', $user->id)
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
                            $contact->contacted_at ? 'Sudah Dihubungi' : 'Belum Dihubungi',
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

        abort_unless($contact->leader_id === $user->id, 404);

        if (! $contact->contacted_at) {
            $contact->update([
                'contacted_at' => now(),
                'contacted_by_leader_id' => $user->id,
            ]);
        }

        return redirect()->away($contact->whatsapp_url);
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
            $query->whereNotNull('contacted_at');
        }

        if ($uiFilters['status'] === 'uncontacted') {
            $query->whereNull('contacted_at');
        }
    }
}
