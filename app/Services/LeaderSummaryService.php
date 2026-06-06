<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class LeaderSummaryService
{
    /**
     * Populate leader collection with summary attributes.
     * @param Collection<int, User> $leaders
     * @param array{period:string,start_date:?string,end_date:?string} $filters
     * @param array{q:?string,status:string,per_page:int} $uiFilters
     * @return Collection<int, User>
     */
    public function populateSummaries(Collection $leaders, array $filters, array $uiFilters): Collection
    {
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

        // today's contacted counts
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

        foreach ($leaders as $leader) {
            $row = $summaryRows->get($leader->id);
            $subRow = $subLeaderSummaryRows->get($leader->id);
            $monthlyRow = $monthlyContactedRows->get($leader->id);
            $monthlySubRow = $monthlySubLeaderContactedRows->get($leader->id);

            $leader->setAttribute('contacts_as_leader_count', (int) (($row->total_contacts ?? 0) + ($subRow->total_contacts ?? 0)));

            $todayDirect = $todayContactedRows->get($leader->id);
            $todaySub = $todaySubLeaderContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_count', (int) (($todayDirect->today_contacted_count ?? 0) + ($todaySub->today_contacted_count ?? 0)));

            $leader->setAttribute('contacted_contacts_monthly_count', (int) (($monthlyRow->monthly_contacted_count ?? 0) + ($monthlySubRow->monthly_contacted_count ?? 0)));

            $latestInputAt = $row->latest_input_at ?? null;
            if ($subRow && $subRow->latest_input_at && (! $latestInputAt || $subRow->latest_input_at > $latestInputAt)) {
                $latestInputAt = $subRow->latest_input_at;
            }
            $leader->setAttribute('contacts_as_leader_max_created_at', $latestInputAt);
        }

        return $leaders;
    }

        // Filters are handled by ContactFilter service
}
