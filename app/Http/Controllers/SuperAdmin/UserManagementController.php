<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Services\ContactImportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('superadmin.users.index', [
            'leaders' => User::where('role', User::ROLE_LEADER)
                ->withCount('subLeaders')
                ->orderBy('name')
                ->get(),
            'subLeaders' => User::where('role', User::ROLE_SUB_LEADER)
                ->with('leader:id,name')
                ->orderBy('name')
                ->get(),
            'teams' => Schema::hasTable('teams')
                ? Team::withCount(['members', 'leaders', 'subLeaders'])->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function storeTeam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')],
        ]);

        Team::create($validated);

        return back()->with('success', 'Tim berhasil dibuat.');
    }

    public function storeLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'team_id' => ['required', Rule::exists('teams', 'id')],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => User::ROLE_LEADER,
            'team_id' => $validated['team_id'],
        ]);

        return back()->with('success', 'Marketing Utama berhasil dibuat.');
    }

    public function storeSubLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'team_id' => ['required', Rule::exists('teams', 'id')],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => User::ROLE_SUB_LEADER,
            'team_id' => $validated['team_id'],
        ]);

        return back()->with('success', 'Asisten Marketing berhasil dibuat.');
    }

    public function assignTeam(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ]);

        $user->update(['team_id' => $validated['team_id']]);

        return back()->with('success', 'Tim berhasil diubah.');
    }

    public function importForm(): View
    {
        return view('superadmin.import', [
            'teams' => Team::orderBy('name')->get(),
        ]);
    }

    public function import(Request $request, ContactImportService $contactImportService): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', Rule::exists('teams', 'id')],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'leader_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_LEADER))],
            'sub_leader_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_SUB_LEADER))],
        ]);

        $rows = $contactImportService->extractRows($request->file('file'));
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau format kolom tidak dikenali.']);
        }

        $summary = $contactImportService->importRows($rows, [
            'team_id' => $validated['team_id'],
            'input_by' => auth()->id(),
            'sub_leader_id' => $validated['sub_leader_id'] ?? null,
            'leader_id' => $validated['leader_id'] ?? null,
        ]);

        return back()->with(
            'success',
            "Import selesai. Berhasil: {$summary['created']}, Duplikat: {$summary['skipped_duplicate']}, Tidak valid: {$summary['skipped_invalid']}."
        );
    }

    public function contactsIndex(Request $request): View
    {
        $filters = $this->resolveDateFilter($request);
        $uiFilters = $this->resolveUiFilters($request);
        $selectedLeaderId = $request->integer('leader_id');

        $leaders = User::where('role', User::ROLE_LEADER)
            ->withCount('subLeaders')
            ->orderBy('id')
            ->get();

        $summaryQuery = Contact::query();
        \App\Services\ContactFilter::applyDateFilter($summaryQuery, $filters);
        \App\Services\ContactFilter::applyListFilters($summaryQuery, $uiFilters);

        $summaryRows = (clone $summaryQuery)
            ->selectRaw("leader_id, COUNT(*) as total_contacts, SUM(CASE WHEN is_contacted = 1 THEN 1 ELSE 0 END) as contacted_contacts, MAX(created_at) as latest_input_at")
            ->groupBy('leader_id')
            ->get()
            ->keyBy('leader_id');

        $subLeaderSummaryRows = (clone $summaryQuery)
            ->join('users as sub_leaders', 'contacts.sub_leader_id', '=', 'sub_leaders.id')
            ->whereNotNull('sub_leaders.leader_id')
            ->selectRaw('sub_leaders.leader_id as leader_id, COUNT(*) as total_contacts, SUM(CASE WHEN contacts.is_contacted = 1 THEN 1 ELSE 0 END) as contacted_contacts, MAX(contacts.created_at) as latest_input_at')
            ->groupBy('sub_leaders.leader_id')
            ->get()
            ->keyBy('leader_id');

        $monthlyContactedRows = (clone $summaryQuery)
            ->where('is_contacted', true)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereYear('status_updated_at', now()->year)
                        ->whereMonth('status_updated_at', now()->month);
                })->orWhere(function ($query) {
                    $query->whereNull('status_updated_at')
                        ->whereYear('contacted_at', now()->year)
                        ->whereMonth('contacted_at', now()->month);
                });
            })
            ->selectRaw('leader_id, COUNT(*) as monthly_contacted_count')
            ->groupBy('leader_id')
            ->get()
            ->keyBy('leader_id');

        // contacted today (per day) - direct
        $todayContactedRows = (clone $summaryQuery)
            ->where('is_contacted', true)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereDate('status_updated_at', now()->toDateString());
                })->orWhere(function ($query) {
                    $query->whereNull('status_updated_at')
                        ->whereDate('contacted_at', now()->toDateString());
                });
            })
            ->selectRaw('leader_id, COUNT(*) as today_contacted_count')
            ->groupBy('leader_id')
            ->get()
            ->keyBy('leader_id');

        // contacted today by sub-leaders
        $todaySubLeaderContactedRows = (clone $summaryQuery)
            ->where('is_contacted', true)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereDate('status_updated_at', now()->toDateString());
                })->orWhere(function ($query) {
                    $query->whereNull('status_updated_at')
                        ->whereDate('contacted_at', now()->toDateString());
                });
            })
            ->join('users as sub_leaders', 'contacts.sub_leader_id', '=', 'sub_leaders.id')
            ->whereNotNull('sub_leaders.leader_id')
            ->selectRaw('sub_leaders.leader_id as leader_id, COUNT(*) as today_contacted_count')
            ->groupBy('sub_leaders.leader_id')
            ->get()
            ->keyBy('leader_id');

        $monthlySubLeaderContactedRows = (clone $summaryQuery)
            ->where('is_contacted', true)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereYear('status_updated_at', now()->year)
                        ->whereMonth('status_updated_at', now()->month);
                })->orWhere(function ($query) {
                    $query->whereNull('status_updated_at')
                        ->whereYear('contacted_at', now()->year)
                        ->whereMonth('contacted_at', now()->month);
                });
            })
            ->join('users as sub_leaders', 'contacts.sub_leader_id', '=', 'sub_leaders.id')
            ->whereNotNull('sub_leaders.leader_id')
            ->selectRaw('sub_leaders.leader_id as leader_id, COUNT(*) as monthly_contacted_count')
            ->groupBy('sub_leaders.leader_id')
            ->get()
            ->keyBy('leader_id');

        foreach ($leaders as $leader) {
            $row = $summaryRows->get($leader->id);
            $subRow = $subLeaderSummaryRows->get($leader->id);
            $monthlyRow = $monthlyContactedRows->get($leader->id);
            $monthlySubRow = $monthlySubLeaderContactedRows->get($leader->id);

            $leader->setAttribute('contacts_as_leader_count', (int) (($row->total_contacts ?? 0) + ($subRow->total_contacts ?? 0)));
            // contacted today (per day)
            $todayDirect = $todayContactedRows->get($leader->id);
            $todaySub = $todaySubLeaderContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_count', (int) (($todayDirect->today_contacted_count ?? 0) + ($todaySub->today_contacted_count ?? 0)));
            // contacted month (rekapan selama bulan berjalan)
            $leader->setAttribute('contacted_contacts_monthly_count', (int) (($monthlyRow->monthly_contacted_count ?? 0) + ($monthlySubRow->monthly_contacted_count ?? 0)));

            $latestInputAt = $row->latest_input_at ?? null;
            if ($subRow && $subRow->latest_input_at && (! $latestInputAt || $subRow->latest_input_at > $latestInputAt)) {
                $latestInputAt = $subRow->latest_input_at;
            }
            $leader->setAttribute('contacts_as_leader_max_created_at', $latestInputAt);
        }

        $leaderNumberMap = $leaders
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn (int $id, int $index) => [$id => $index + 1]);

        $summaryLeaders = $selectedLeaderId > 0
            ? $leaders->where('id', $selectedLeaderId)->values()
            : $leaders;

        $contactsQuery = Contact::query()
            ->with(['leader:id,name', 'subLeader:id,name'])
            ->latest();
        \App\Services\ContactFilter::applyDateFilter($contactsQuery, $filters);
        \App\Services\ContactFilter::applyListFilters($contactsQuery, $uiFilters);

        if ($selectedLeaderId > 0) {
            $contactsQuery->where(function (Builder $query) use ($selectedLeaderId) {
                $query->where('leader_id', $selectedLeaderId)
                    ->orWhereHas('subLeader', fn (Builder $subLeaderQuery) => $subLeaderQuery->where('leader_id', $selectedLeaderId));
            });
        }

        $perPage = (int) $uiFilters['per_page'];

        return view('superadmin.contacts.index', [
            'leaders' => $leaders,
            'summaryLeaders' => $summaryLeaders,
            'leaderNumberMap' => $leaderNumberMap,
            'selectedLeaderId' => $selectedLeaderId > 0 ? $selectedLeaderId : null,
            'filters' => $filters,
            'uiFilters' => $uiFilters,
            'contacts' => $contactsQuery->paginate($perPage)->withQueryString(),
        ]);
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

    // Filtering helpers are provided by \App\Services\ContactFilter to avoid duplication.

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        // Only allow deleting marketing users (leaders or assistants)
        if (!in_array($user->role, [User::ROLE_LEADER, User::ROLE_SUB_LEADER], true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Hanya user marketing yang dapat dihapus.',
                ], 403);
            }

            return back()->withErrors(['user' => 'Hanya user marketing yang dapat dihapus.']);
        }

        // If deleting a marketing utama (leader), detach it from its sub-leaders and contacts
        if ($user->role === User::ROLE_LEADER) {
            User::where('leader_id', $user->id)->update(['leader_id' => null]);
            Contact::where('leader_id', $user->id)->update(['leader_id' => null]);
        }

        // If deleting an asisten marketing, detach it from contacts
        if ($user->role === User::ROLE_SUB_LEADER) {
            Contact::where('sub_leader_id', $user->id)->update(['sub_leader_id' => null]);
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Data user berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'User marketing berhasil dihapus.');
    }
}
