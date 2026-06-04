<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class ContactFilter
{
    private function __construct()
    {
    }

    public static function applyDateFilter(Builder $query, array $filters): void
    {
        $period = $filters['period'] ?? 'all';

        if ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(6)->startOfDay());
            return;
        }

        if ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(29)->startOfDay());
            return;
        }

        if ($period === 'custom') {
            $startDate = $filters['start_date'] ? Carbon::parse($filters['start_date'])->startOfDay() : null;
            $endDate = $filters['end_date'] ? Carbon::parse($filters['end_date'])->endOfDay() : null;

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

    public static function applyListFilters(Builder $query, array $uiFilters): void
    {
        if (! empty($uiFilters['q'])) {
            $keyword = $uiFilters['q'];
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder
                    ->where('contact_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhereHas('leader', fn (Builder $leaderQuery) => $leaderQuery->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('subLeader', fn (Builder $subLeaderQuery) => $subLeaderQuery->where('name', 'like', "%{$keyword}%"));
            });
        }

        if (($uiFilters['status'] ?? 'all') === 'contacted') {
            $query->where('is_contacted', true);
        }

        if (($uiFilters['status'] ?? 'all') === 'uncontacted') {
            $query->where('is_contacted', false);
        }
    }
}
