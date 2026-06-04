<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardRecapService
{
    public function getDashboardData(User $user, Request $request): array
    {
        if ($user->role === User::ROLE_SUPERADMIN) {
            return $this->getSuperAdminDashboardData($request);
        }

        if ($user->role === User::ROLE_MAIN_MARKETING) {
            return $this->getMainMarketingDashboardData($user);
        }

        return $this->getAssistantMarketingDashboardData($user);
    }

    private function getSuperAdminDashboardData(Request $request): array
    {
        $stats = $this->buildSuperAdminStats();
        $meta = $this->buildSuperAdminMeta();
        
        $selectedMonth = $this->getSelectedMonth($request);
        [$year, $month] = $this->parseYearAndMonth($selectedMonth);
        
        $leaderChartDate = $this->getLeaderChartDate($request);
        $subLeaderChartDate = $this->getSubLeaderChartDate($request);

        $leaderComparisonData = $this->getLeaderComparisonDataForDay($leaderChartDate);
        $subLeaderComparisonData = $this->getSubLeaderComparisonDataForDay($subLeaderChartDate);
        
        $teamComparisonData = $this->getTeamComparisonData($year, $month);
        $monthlyTotalsData = $this->buildMonthlyTotalsData($year, $month);

        return array_merge($this->emptyPayload(), [
            'stats' => $stats,
            'meta' => $meta,
            'leaderComparisonData' => $leaderComparisonData,
            'subLeaderComparisonData' => $subLeaderComparisonData,
            'teamComparisonData' => $teamComparisonData,
            'monthlyTotalsData' => $monthlyTotalsData,
            'selectedMonth' => $selectedMonth,
            'leaderChartDate' => $leaderChartDate,
            'subLeaderChartDate' => $subLeaderChartDate,
        ]);
    }

    private function getMainMarketingDashboardData(User $user): array
    {
        $teamContacts = Contact::query()
            ->when(
                $user->team_id,
                fn ($query) => $query->where('team_id', $user->team_id),
                fn ($query) => $query->whereRaw('1 = 0')
            );

        $contacts = (clone $teamContacts)->count();
        $contacted = (clone $teamContacts)->where('is_contacted', true)->count();

        $personalHandled = (clone $teamContacts)
            ->where('is_contacted', true)
            ->where('status_updated_by', $user->id)
            ->count();

        $assistantSubLeaders = User::query()
            ->where('role', User::ROLE_ASSISTANT_MARKETING)
            ->when(
                $user->team_id,
                fn ($query) => $query->where('team_id', $user->team_id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->withCount(['contactsEntered as contacts_entered_count'])
            ->orderByDesc('contacts_entered_count')
            ->get();

        $assistantChartData = $assistantSubLeaders->map(function ($subLeader) {
            return [
                'label' => $subLeader->name,
                'count' => $subLeader->contacts_entered_count,
            ];
        })->all();

        $mainTargetData = $this->buildMainTargetData($personalHandled);
        $stats = $this->buildMainMarketingStats($contacts, $contacted, $assistantSubLeaders->count(), $mainTargetData['progress']);

        $dateLabels = $this->buildDateLabels(7);
        $mainDailyData = $this->pluckDailyCounts(
            (clone $teamContacts)
                ->where('is_contacted', true)
                ->where('status_updated_by', $user->id)
                ->whereNotNull('status_updated_at'),
            'status_updated_at',
            $dateLabels
        );

        $assistantDailyData = $this->pluckDailyCounts(
            (clone $teamContacts)->whereNotNull('assistant_marketing_id'),
            'created_at',
            $dateLabels
        );

        $mainDailyTargetData = $this->buildDailyTargetData(User::TARGET_MAIN_MARKETING, 7);
        $assistantDailyTargetData = $this->buildDailyTargetData(User::TARGET_ASSISTANT_MARKETING, 7);

        return array_merge($this->emptyPayload(), [
            'stats' => $stats,
            'assistantChartData' => $assistantChartData,
            'mainTargetData' => $mainTargetData,
            'mainDailyData' => $mainDailyData,
            'mainDailyTargetData' => $mainDailyTargetData,
            'assistantDailyData' => $assistantDailyData,
            'assistantDailyTargetData' => $assistantDailyTargetData,
            'stats' => array_merge($stats, [
                'daily_labels' => $this->formatDailyLabels($dateLabels),
            ]),
        ]);
    }

    private function getAssistantMarketingDashboardData(User $user): array
    {
        $contacts = Contact::where('assistant_marketing_id', $user->id)->count();

        $stats = [
            'contacts' => $contacts,
            'target' => User::TARGET_ASSISTANT_MARKETING,
            'progress' => User::TARGET_ASSISTANT_MARKETING > 0
                ? min(100, (int) round(($contacts / User::TARGET_ASSISTANT_MARKETING) * 100))
                : 0,
        ];

        $dateLabels = $this->buildDateLabels(7);
        $subLeaderDailyData = $this->pluckDailyCounts(
            Contact::where('assistant_marketing_id', $user->id),
            'created_at',
            $dateLabels
        );

        $subLeaderDailyTargetData = $this->buildDailyTargetData(User::TARGET_ASSISTANT_MARKETING, 7);

        return array_merge($this->emptyPayload(), [
            'stats' => array_merge($stats, [
                'daily_labels' => $this->formatDailyLabels($dateLabels),
            ]),
            'subLeaderDailyData' => $subLeaderDailyData,
            'subLeaderDailyTargetData' => $subLeaderDailyTargetData,
        ]);
    }

    private function buildSuperAdminStats(): array
    {
        $leadersCount = User::where('role', User::ROLE_MAIN_MARKETING)->count();
        $subLeadersCount = User::where('role', User::ROLE_ASSISTANT_MARKETING)->count();
        $contactsCount = Contact::count();

        return [
            'leaders' => $leadersCount,
            'sub_leaders' => $subLeadersCount,
            'contacts' => $contactsCount,
            'avg_per_sub_leader' => $subLeadersCount > 0
                ? (int) round($contactsCount / $subLeadersCount)
                : 0,
        ];
    }

    private function buildSuperAdminMeta(): array
    {
        $topLeader = User::where('role', User::ROLE_MAIN_MARKETING)
            ->withCount('contactsAsLeader')
            ->orderByDesc('contacts_as_leader_count')
            ->first();

        $topSubLeader = User::where('role', User::ROLE_ASSISTANT_MARKETING)
            ->withCount('contactsEntered')
            ->orderByDesc('contacts_entered_count')
            ->first();

        return [
            'sub_leaders_without_leader' => User::where('role', User::ROLE_ASSISTANT_MARKETING)
                ->whereNull('main_marketing_id')
                ->count(),
            'top_leader' => $topLeader,
            'top_sub_leader' => $topSubLeader,
        ];
    }

    private function getLeaderComparisonDataForDay(string $date): Collection
    {
        return User::where('role', User::ROLE_MAIN_MARKETING)
            ->leftJoin('contacts', function ($join) use ($date) {
                $join->on('users.id', '=', 'contacts.main_marketing_id')
                    ->whereDate('contacts.created_at', $date);
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(contacts.id) as total_count'),
                 DB::raw("SUM(CASE WHEN contacts.is_contacted = 1 THEN 1 ELSE 0 END) as contacted_count")
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_count')
            ->get()
            ->map(fn ($item) => $this->mapLeaderComparisonRow($item));
    }

    private function getLeaderComparisonData(int $year, int $month): Collection
    {
        return User::where('role', User::ROLE_MAIN_MARKETING)
            ->leftJoin('contacts', function ($join) use ($year, $month) {
                $join->on('users.id', '=', 'contacts.main_marketing_id')
                    ->whereYear('contacts.created_at', $year)
                    ->whereMonth('contacts.created_at', $month);
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(contacts.id) as total_count'),
                 DB::raw("SUM(CASE WHEN contacts.is_contacted = 1 THEN 1 ELSE 0 END) as contacted_count")
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_count')
            ->get()
            ->map(fn ($item) => $this->mapLeaderComparisonRow($item));
    }

    private function mapLeaderComparisonRow(object $item): array
    {
        $totalCount = (int) $item->total_count;
        $contactedCount = (int) $item->contacted_count;

        return [
            'label' => $item->name,
            'total' => $totalCount,
            'contacted' => $contactedCount,
            'uncontacted' => max($totalCount - $contactedCount, 0),
        ];
    }

    private function getSubLeaderComparisonData(int $year, int $month): Collection
    {
        return User::where('users.role', User::ROLE_ASSISTANT_MARKETING)
            ->leftJoin('users as leaders', 'users.main_marketing_id', '=', 'leaders.id')
            ->leftJoin('contacts', function ($join) use ($year, $month) {
                $join->on('users.id', '=', 'contacts.assistant_marketing_id')
                    ->whereYear('contacts.created_at', $year)
                    ->whereMonth('contacts.created_at', $month);
            })
            ->select(
                'users.id',
                'users.name',
                'leaders.name as leader_name',
                DB::raw('COUNT(contacts.id) as total_count'),
                 DB::raw("SUM(CASE WHEN contacts.is_contacted = 1 THEN 1 ELSE 0 END) as contacted_count")
            )
            ->groupBy('users.id', 'users.name', 'leaders.name')
            ->orderByDesc('total_count')
            ->get()
            ->map(function ($item) {
                $totalCount = (int) $item->total_count;
                $contactedCount = (int) $item->contacted_count;

                return [
                    'label' => $item->name,
                    'group' => $item->leader_name ?? 'Tanpa Leader',
                    'total' => $totalCount,
                    'contacted' => $contactedCount,
                    'uncontacted' => max($totalCount - $contactedCount, 0),
                ];
            });
    }

    private function getSubLeaderComparisonDataForDay(string $date): Collection
    {
        return User::where('users.role', User::ROLE_ASSISTANT_MARKETING)
            ->leftJoin('users as leaders', 'users.main_marketing_id', '=', 'leaders.id')
            ->leftJoin('contacts', function ($join) use ($date) {
                $join->on('users.id', '=', 'contacts.assistant_marketing_id')
                    ->whereDate('contacts.created_at', $date);
            })
            ->select(
                'users.id',
                'users.name',
                'leaders.name as leader_name',
                DB::raw('COUNT(contacts.id) as total_count'),
                 DB::raw("SUM(CASE WHEN contacts.is_contacted = 1 THEN 1 ELSE 0 END) as contacted_count")
            )
            ->groupBy('users.id', 'users.name', 'leaders.name')
            ->orderByDesc('total_count')
            ->get()
            ->map(function ($item) {
                $totalCount = (int) $item->total_count;
                $contactedCount = (int) $item->contacted_count;

                return [
                    'label' => $item->name,
                    'group' => $item->leader_name ?? 'Tanpa Leader',
                    'total' => $totalCount,
                    'contacted' => $contactedCount,
                    'uncontacted' => max($totalCount - $contactedCount, 0),
                ];
            });
    }

    private function getTeamComparisonData(int $year, int $month): Collection
    {
        if (! Schema::hasTable('teams')) {
            return collect();
        }

        return Team::query()
            ->leftJoin('users', 'teams.id', '=', 'users.team_id')
            ->leftJoin('contacts', function ($join) use ($year, $month) {
                $join->on(function ($query) {
                    $query->on('contacts.main_marketing_id', '=', 'users.id')
                        ->orOn('contacts.assistant_marketing_id', '=', 'users.id');
                })
                ->whereYear('contacts.created_at', $year)
                ->whereMonth('contacts.created_at', $month);
            })
            ->select(
                'teams.id',
                'teams.name',
                DB::raw('COUNT(DISTINCT contacts.id) as total_count'),
                 DB::raw("COUNT(DISTINCT CASE WHEN contacts.is_contacted = 1 THEN contacts.id END) as contacted_count")
            )
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('total_count')
            ->get()
            ->map(function ($item) {
                $totalCount = (int) $item->total_count;
                $contactedCount = (int) $item->contacted_count;

                return [
                    'label' => $item->name,
                    'total' => $totalCount,
                    'contacted' => $contactedCount,
                    'uncontacted' => max($totalCount - $contactedCount, 0),
                ];
            });
    }

    private function buildMonthlyTotalsData(int $year, int $month): Collection
    {
        $startMonth = now()->subMonths(11)->startOfMonth();
        $endMonth = now()->endOfMonth();

        $contactedRows = Contact::query()
            ->where('is_contacted', true)
            ->whereNotNull('status_updated_at')
            ->whereBetween('status_updated_at', [$startMonth, $endMonth])
            ->selectRaw('YEAR(status_updated_at) as year, MONTH(status_updated_at) as month, COUNT(*) as total_count')
            ->groupBy('year', 'month')
            ->get();

        $inputRows = Contact::query()
            ->whereNotNull('assistant_marketing_id')
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total_count')
            ->groupBy('year', 'month')
            ->get();

        $contactedMap = $this->buildMonthlyMap($contactedRows);
        $inputMap = $this->buildMonthlyMap($inputRows);

        return collect(range(0, 11))
            ->map(function (int $offset) use ($startMonth, $contactedMap, $inputMap) {
                $date = (clone $startMonth)->addMonths($offset);
                $key = $date->format('Y-m');

                return [
                    'key' => $key,
                    'label' => Carbon::create($date->year, $date->month)->translatedFormat('M Y'),
                    'contacted_total' => $contactedMap->get($key, 0),
                    'input_total' => $inputMap->get($key, 0),
                ];
            });
    }

    private function buildMonthlyMap(Collection $rows): Collection
    {
        return $rows->mapWithKeys(function ($row) {
            $key = sprintf('%04d-%02d', (int) $row->year, (int) $row->month);

            return [$key => (int) $row->total_count];
        });
    }

    private function buildMainTargetData(int $contacted): array
    {
        return [
            'target' => User::TARGET_MAIN_MARKETING,
            'contacted' => $contacted,
            'remaining' => max(User::TARGET_MAIN_MARKETING - $contacted, 0),
            'progress' => User::TARGET_MAIN_MARKETING > 0
                ? min(100, (int) round(($contacted / User::TARGET_MAIN_MARKETING) * 100))
                : 0,
        ];
    }

    private function buildMainMarketingStats(int $contacts, int $contacted, int $subLeadersCount, int $progress): array
    {
        return [
            'contacts' => $contacts,
            'contacted' => $contacted,
            'sub_leaders' => $subLeadersCount,
            'target' => User::TARGET_MAIN_MARKETING,
            'progress' => $progress,
        ];
    }

    private function buildDateLabels(int $days): Collection
    {
        return collect(range($days - 1, 0))
            ->map(fn (int $offset) => now()->subDays($offset)->format('Y-m-d'));
    }

    private function formatDailyLabels(Collection $dateLabels): array
    {
        return $dateLabels->map(fn (string $date) => Carbon::parse($date)->translatedFormat('d M'))->all();
    }

    private function pluckDailyCounts($query, string $dateColumn, Collection $dateLabels): array
    {
        $dailyCounts = $query
            ->whereBetween($dateColumn, [$dateLabels->first() . ' 00:00:00', now()->endOfDay()])
            ->selectRaw("DATE({$dateColumn}) as date, COUNT(*) as total")
            ->groupBy('date')
            ->pluck('total', 'date');

        return $dateLabels->map(fn (string $date) => (int) ($dailyCounts[$date] ?? 0))->all();
    }

    private function buildDailyTargetData(int $target, int $length): array
    {
        return array_fill(0, $length, $target);
    }

    private function getSelectedMonth(Request $request): string
    {
        return $request->input('leader_chart_month', now()->format('Y-m'));
    }

    private function getLeaderChartDate(Request $request): string
    {
        $date = $request->input('leader_chart_date', now()->format('Y-m-d'));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->format('Y-m-d');
    }

    private function getSubLeaderChartDate(Request $request): string
    {
        $date = $request->input('sub_leader_chart_date', now()->format('Y-m-d'));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->format('Y-m-d');
    }

    private function parseYearAndMonth(string $selectedMonth): array
    {
        [$year, $month] = explode('-', $selectedMonth);

        return [(int) $year, (int) $month];
    }

    private function emptyPayload(): array
    {
        return [
            'stats' => [],
            'meta' => [],
            'leaderComparisonData' => collect(),
            'subLeaderComparisonData' => collect(),
            'assistantChartData' => [],
            'mainTargetData' => [
                'target' => 0,
                'contacted' => 0,
                'remaining' => 0,
                'progress' => 0,
            ],
            'mainDailyData' => [],
            'mainDailyTargetData' => [],
            'assistantDailyData' => [],
            'assistantDailyTargetData' => [],
            'subLeaderDailyData' => [],
            'subLeaderDailyTargetData' => [],
            'monthlyTotalsData' => collect(),
            'selectedMonth' => null,
            'leaderChartDate' => null,
            'subLeaderChartDate' => null,
        ];
    }
}
