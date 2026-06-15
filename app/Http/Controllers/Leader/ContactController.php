<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactFilter;
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
        $filters = ContactFilter::resolveDateFilter($request);
        $uiFilters = ContactFilter::resolveUiFilters($request);
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
                    ContactFilter::applyDateFilter($query, $filters);
                    ContactFilter::applyListFilters($query, $uiFilters, false);
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
        ContactFilter::applyDateFilter($contactsQuery, $filters);
        ContactFilter::applyListFilters($contactsQuery, $uiFilters, false);

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

        $todayContactedCount = $user->team_id
            ? Contact::query()
                ->where('team_id', $user->team_id)
                ->where('is_contacted', true)
                ->where('contacted_by_leader_id', $user->id)
                ->whereDate('status_updated_at', now()->toDateString())
                ->count()
            : 0;

        $target = User::TARGET_LEADER;
        $progress = $target > 0 ? min(100, (int) round(($todayContactedCount / $target) * 100)) : 0;

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
            'todayContactedCount' => $todayContactedCount,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $filters = ContactFilter::resolveDateFilter($request);
        $uiFilters = ContactFilter::resolveUiFilters($request);
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

            ContactFilter::applyDateFilter($contactsQuery, $filters);
            ContactFilter::applyListFilters($contactsQuery, $uiFilters, false);

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

    private function scopedContacts(User $user): Builder
    {
        $query = Contact::query();
        $this->applyLeaderContactScope($query, $user);

        return $query;
    }

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
}
