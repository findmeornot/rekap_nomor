<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request): View
    {
        $user = auth()->user();

        $stats = [];
        $meta = [];
        $leaderComparisonData = collect();
        $subLeaderComparisonData = collect();
        $monthlyTotalsData = collect();
        $selectedMonth = null;

        if ($user->role === User::ROLE_SUPERADMIN) {
            $leadersCount = User::where('role', User::ROLE_LEADER)->count();
            $subLeadersCount = User::where('role', User::ROLE_SUB_LEADER)->count();
            $contactsCount = Contact::count();

            $topLeader = User::where('role', User::ROLE_LEADER)
                ->withCount('contactsAsLeader')
                ->orderByDesc('contacts_as_leader_count')
                ->first();

            $topSubLeader = User::where('role', User::ROLE_SUB_LEADER)
                ->withCount('contactsEntered')
                ->orderByDesc('contacts_entered_count')
                ->first();

            $stats = [
                'leaders' => $leadersCount,
                'sub_leaders' => $subLeadersCount,
                'contacts' => $contactsCount,
                'avg_per_sub_leader' => $subLeadersCount > 0 ? (int) round($contactsCount / $subLeadersCount) : 0,
            ];

            $meta = [
                'sub_leaders_without_leader' => User::where('role', User::ROLE_SUB_LEADER)
                    ->whereNull('leader_id')
                    ->count(),
                'top_leader' => $topLeader,
                'top_sub_leader' => $topSubLeader,
            ];

            $selectedMonth = $request->input('leader_chart_month', now()->format('Y-m'));
            $year = (int) explode('-', $selectedMonth)[0];
            $month = (int) explode('-', $selectedMonth)[1];

            $leaderComparisonData = User::where('role', User::ROLE_LEADER)
                ->leftJoin('contacts', function ($join) use ($year, $month) {
                    $join->on('users.id', '=', 'contacts.leader_id')
                        ->whereYear('contacts.created_at', $year)
                        ->whereMonth('contacts.created_at', $month);
                })
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(contacts.id) as total_count'),
                    DB::raw('SUM(CASE WHEN contacts.contacted_at IS NOT NULL THEN 1 ELSE 0 END) as contacted_count')
                )
                ->groupBy('users.id', 'users.name')
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

            $subLeaderComparisonData = User::where('users.role', User::ROLE_SUB_LEADER)
                ->leftJoin('users as leaders', 'users.leader_id', '=', 'leaders.id')
                ->leftJoin('contacts', function ($join) use ($year, $month) {
                    $join->on('users.id', '=', 'contacts.sub_leader_id')
                        ->whereYear('contacts.created_at', $year)
                        ->whereMonth('contacts.created_at', $month);
                })
                ->select(
                    'users.id',
                    'users.name',
                    'leaders.name as leader_name',
                    DB::raw('COUNT(contacts.id) as total_count'),
                    DB::raw('SUM(CASE WHEN contacts.contacted_at IS NOT NULL THEN 1 ELSE 0 END) as contacted_count')
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

            $startMonth = now()->subMonths(11)->startOfMonth();
            $endMonth = now()->endOfMonth();

            $contactedRows = Contact::query()
                ->whereNotNull('contacted_at')
                ->whereBetween('contacted_at', [$startMonth, $endMonth])
                ->selectRaw('YEAR(contacted_at) as year, MONTH(contacted_at) as month, COUNT(*) as total_count')
                ->groupBy('year', 'month')
                ->get();

            $inputRows = Contact::query()
                ->whereNotNull('sub_leader_id')
                ->whereBetween('created_at', [$startMonth, $endMonth])
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total_count')
                ->groupBy('year', 'month')
                ->get();

            $contactedMap = $contactedRows->mapWithKeys(function ($row) {
                $key = sprintf('%04d-%02d', (int) $row->year, (int) $row->month);
                return [$key => (int) $row->total_count];
            });

            $inputMap = $inputRows->mapWithKeys(function ($row) {
                $key = sprintf('%04d-%02d', (int) $row->year, (int) $row->month);
                return [$key => (int) $row->total_count];
            });

            $monthlyTotalsData = collect(range(0, 11))
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
        } elseif ($user->role === User::ROLE_LEADER) {
            $stats = [
                'contacts' => Contact::where('leader_id', $user->id)->count(),
                'contacted' => Contact::where('leader_id', $user->id)->whereNotNull('contacted_at')->count(),
                'sub_leaders' => User::where('leader_id', $user->id)->count(),
            ];
        } else {
            $stats = [
                'contacts' => Contact::where('sub_leader_id', $user->id)->count(),
            ];
        }

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'meta' => $meta,
            'leaderComparisonData' => $leaderComparisonData,
            'subLeaderComparisonData' => $subLeaderComparisonData,
            'monthlyTotalsData' => $monthlyTotalsData,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
