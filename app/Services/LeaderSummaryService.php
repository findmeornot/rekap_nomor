<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LeaderSummaryService
{
    /**
     * Populate leader collection with summary attributes.
     *
     * "Sudah Dihubungi" counts use contacted_by_leader_id so multiple leaders
     * on the same team are tracked separately.
     *
     * "Input Terakhir" uses team_id join because sub-leaders input contacts for
     * the whole team without a direct leader_id on each row.
     *
     * @param Collection<int, User> $leaders
     * @param array{period:string,start_date:?string,end_date:?string} $filters
     * @param array{q:?string,status:string,per_page:int} $uiFilters
     * @return Collection<int, User>
     */
    public function populateSummaries(Collection $leaders, array $filters, array $uiFilters): Collection
    {
        $todayContactedRows = $this->queryTodayContacted();
        $monthlyContactedRows = $this->queryMonthlyContacted();
        $latestInputRows = $this->queryLatestInput($filters, $uiFilters);

        foreach ($leaders as $leader) {
            $todayRow = $todayContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_count', (int) ($todayRow->today_contacted_count ?? 0));

            $monthlyRow = $monthlyContactedRows->get($leader->id);
            $leader->setAttribute('contacted_contacts_monthly_count', (int) ($monthlyRow->monthly_contacted_count ?? 0));

            $latestRow = $latestInputRows->get($leader->id);
            $leader->setAttribute('contacts_as_leader_max_created_at', $latestRow->latest_input_at ?? null);
        }

        return $leaders;
    }

    private function queryTodayContacted(): \Illuminate\Support\Collection
    {
        return Contact::query()
            ->whereNotNull('contacted_by_leader_id')
            ->where('is_contacted', true)
            ->whereDate('status_updated_at', now()->toDateString())
            ->selectRaw('contacted_by_leader_id as leader_id, COUNT(*) as today_contacted_count')
            ->groupBy('contacted_by_leader_id')
            ->get()
            ->keyBy('leader_id');
    }

    private function queryMonthlyContacted(): \Illuminate\Support\Collection
    {
        return Contact::query()
            ->whereNotNull('contacted_by_leader_id')
            ->where('is_contacted', true)
            ->whereYear('status_updated_at', now()->year)
            ->whereMonth('status_updated_at', now()->month)
            ->selectRaw('contacted_by_leader_id as leader_id, COUNT(*) as monthly_contacted_count')
            ->groupBy('contacted_by_leader_id')
            ->get()
            ->keyBy('leader_id');
    }

    /**
     * @param array{period:string,start_date:?string,end_date:?string} $filters
     * @param array{q:?string,status:string,per_page:int} $uiFilters
     */
    private function queryLatestInput(array $filters, array $uiFilters): \Illuminate\Support\Collection
    {
        $query = Contact::query();
        ContactFilter::applyDateFilter($query, $filters);
        ContactFilter::applyListFilters($query, $uiFilters);

        return $query
            ->join('users as team_leaders', function ($join) {
                $join->on('contacts.team_id', '=', 'team_leaders.team_id')
                    ->where('team_leaders.role', User::ROLE_LEADER);
            })
            ->selectRaw('team_leaders.id as leader_id, MAX(contacts.created_at) as latest_input_at')
            ->groupBy('team_leaders.id')
            ->get()
            ->keyBy('leader_id');
    }
}
