<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request): View
    {
        $user = auth()->user();

        $stats = [];
        $meta = [];
        $leaderContactedData = collect();
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

            $leaderContactedData = User::where('role', User::ROLE_LEADER)
                ->leftJoin('contacts', function ($join) use ($year, $month) {
                    $join->on('users.id', '=', 'contacts.leader_id')
                        ->whereNotNull('contacts.contacted_at')
                        ->whereYear('contacts.contacted_at', $year)
                        ->whereMonth('contacts.contacted_at', $month);
                })
                ->select('users.id', 'users.name', DB::raw('COUNT(contacts.id) as contacted_count'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('contacted_count')
                ->get()
                ->map(function ($item) {
                    return [
                        'label' => $item->name,
                        'count' => (int) $item->contacted_count,
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
            'leaderContactedData' => $leaderContactedData,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
