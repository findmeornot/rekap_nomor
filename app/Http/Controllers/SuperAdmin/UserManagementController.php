<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('superadmin.users.index', [
            'leaders' => User::where('role', User::ROLE_MAIN_MARKETING)
                ->withCount('subLeaders')
                ->orderBy('name')
                ->get(),
            'subLeaders' => User::where('role', User::ROLE_ASSISTANT_MARKETING)
                ->with('leader:id,name')
                ->orderBy('name')
                ->get(),
            'teams' => Schema::hasTable('teams') ? Team::withCount('members')->orderBy('name')->get() : collect(),
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

    public function assignTeam(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ]);

        $user->update([
            'team_id' => $validated['team_id'] ?? null,
        ]);

        return back()->with('success', 'Tim user berhasil diperbarui.');
    }

    public function storeLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ]);

        User::create([
            ...$validated,
            'role' => User::ROLE_MAIN_MARKETING,
            'main_marketing_id' => null,
        ]);

        return back()->with('success', 'Leader berhasil dibuat.');
    }

    public function storeSubLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'main_marketing_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_MAIN_MARKETING)),
            ],
        ]);

        $leader = User::where('role', User::ROLE_MAIN_MARKETING)
            ->findOrFail($validated['main_marketing_id']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'main_marketing_id' => $leader->id,
            'team_id' => $leader->team_id,
            'role' => User::ROLE_ASSISTANT_MARKETING,
        ]);

        return back()->with('success', 'Sub leader berhasil dibuat.');
    }

    public function assignLeader(Request $request, User $subLeader): RedirectResponse
    {
        abort_unless($subLeader->role === User::ROLE_ASSISTANT_MARKETING, 404);

        $validated = $request->validate([
            'main_marketing_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_MAIN_MARKETING)),
            ],
        ]);

        $leader = User::where('role', User::ROLE_MAIN_MARKETING)
            ->findOrFail($validated['main_marketing_id']);

        $subLeader->update([
            'main_marketing_id' => $leader->id,
            'team_id' => $leader->team_id,
        ]);

        return back()->with('success', 'Leader untuk sub leader berhasil diperbarui.');
    }

    public function contactsIndex(Request $request): View
    {
        $filters = $this->resolveDateFilter($request);
        $uiFilters = $this->resolveUiFilters($request);
        $selectedLeaderId = $request->integer('leader_id');

        $leaders = User::where('role', User::ROLE_MAIN_MARKETING)
            ->withCount('subLeaders')
            ->orderBy('id')
            ->get();

        $summaryQuery = Contact::query();
        $this->applyDateFilter($summaryQuery, $filters);
        $this->applyListFilters($summaryQuery, $uiFilters);

        $summaryRows = $summaryQuery
            ->selectRaw('main_marketing_id, COUNT(*) as total_contacts, SUM(CASE WHEN contacted_at IS NOT NULL THEN 1 ELSE 0 END) as contacted_contacts, MAX(created_at) as latest_input_at')
            ->groupBy('main_marketing_id')
            ->get()
            ->keyBy('main_marketing_id');

        $monthlyContactedRows = Contact::query()
            ->whereNotNull('contacted_at')
            ->whereYear('contacted_at', now()->year)
            ->whereMonth('contacted_at', now()->month)
            ->selectRaw('main_marketing_id, COUNT(*) as monthly_contacted_count')
            ->groupBy('main_marketing_id')
            ->get()
            ->keyBy('main_marketing_id');

        foreach ($leaders as $leader) {
            $row = $summaryRows->get($leader->id);
            $monthlyRow = $monthlyContactedRows->get($leader->id);
            $leader->setAttribute('contacts_as_leader_count', (int) ($row->total_contacts ?? 0));
            $leader->setAttribute('contacted_contacts_count', (int) ($row->contacted_contacts ?? 0));
            $leader->setAttribute('contacted_contacts_monthly_count', (int) ($monthlyRow->monthly_contacted_count ?? 0));
            $leader->setAttribute('contacts_as_leader_max_created_at', $row->latest_input_at ?? null);
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
        $this->applyDateFilter($contactsQuery, $filters);
        $this->applyListFilters($contactsQuery, $uiFilters);

        if ($selectedLeaderId > 0) {
            $contactsQuery->where('main_marketing_id', $selectedLeaderId);
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
                    ->orWhereHas('leader', fn (Builder $leaderQuery) => $leaderQuery->where('name', 'like', "%{$keyword}%"))
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
