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

        // "Sudah Dihubungi" counts use contacted_by_leader_id — tracks which specific
        // leader marked the contact, so multiple leaders on same team stay separate.
        $todayContactedRows = Contact::query()
            ->whereNotNull('contacted_by_leader_id')
            ->where('is_contacted', true)
            ->whereDate('status_updated_at', now()->toDateString())
            ->selectRaw('contacted_by_leader_id as leader_id, COUNT(*) as today_contacted_count')
            ->groupBy('contacted_by_leader_id')
            ->get()
            ->keyBy('leader_id');

        $monthlyContactedRows = Contact::query()
            ->whereNotNull('contacted_by_leader_id')
            ->where('is_contacted', true)
            ->whereYear('status_updated_at', now()->year)
            ->whereMonth('status_updated_at', now()->month)
            ->selectRaw('contacted_by_leader_id as leader_id, COUNT(*) as monthly_contacted_count')
            ->groupBy('contacted_by_leader_id')
            ->get()
            ->keyBy('leader_id');

        // "Input Terakhir" uses team_id join — sub-leaders input for the team,
        // apply any active date/list filters on the base query.
        $summaryQuery = Contact::query();
        \App\Services\ContactFilter::applyDateFilter($summaryQuery, $filters);
        \App\Services\ContactFilter::applyListFilters($summaryQuery, $uiFilters);

        $latestInputRows = (clone $summaryQuery)
            ->join('users as team_leaders', function ($join) {
                $join->on('contacts.team_id', '=', 'team_leaders.team_id')
                    ->where('team_leaders.role', User::ROLE_LEADER);
            })
            ->selectRaw('team_leaders.id as leader_id, MAX(contacts.created_at) as latest_input_at')
            ->groupBy('team_leaders.id')
            ->get()
            ->keyBy('leader_id');

        foreach ($leaders as $leader) {
            $todayRow = $todayContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_count', (int) ($todayRow->today_contacted_count ?? 0));
            $monthlyRow = $monthlyContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_monthly_count', (int) ($monthlyRow->monthly_contacted_count ?? 0));
            $latestRow = $latestInputRows->get($leader->id);
            $leader->setAttribute('contacts_as_leader_max_created_at', $latestRow->latest_input_at ?? null);
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
            $selectedLeader = $leaders->firstWhere('id', $selectedLeaderId);
            if ($selectedLeader && $selectedLeader->team_id) {
                $contactsQuery->where('team_id', $selectedLeader->team_id);
            } else {
                $contactsQuery->whereRaw('1 = 0');
            }
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
