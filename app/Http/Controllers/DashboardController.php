<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $stats = [];
        $meta = [];

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
        ]);
    }
}
