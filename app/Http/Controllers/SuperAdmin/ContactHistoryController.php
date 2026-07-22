<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ContactHistory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q'              => ['nullable', 'string', 'max:100'],
            'archive_period' => ['nullable', 'string', 'max:7', 'regex:/^\d{4}-\d{2}$/'],
            'leader_id'      => ['nullable', 'integer'],
            'sub_leader_id'  => ['nullable', 'integer'],
            'per_page'       => ['nullable', 'in:10,20,50,100'],
        ]);

        $q             = isset($validated['q']) ? trim((string) $validated['q']) : null;
        $archivePeriod = $validated['archive_period'] ?? null;
        $leaderId      = isset($validated['leader_id']) ? (int) $validated['leader_id'] : null;
        $subLeaderId   = isset($validated['sub_leader_id']) ? (int) $validated['sub_leader_id'] : null;
        $perPage       = (int) ($validated['per_page'] ?? 20);

        $query = ContactHistory::query()
            ->with([
                'subLeader:id,name',
                'leader:id,name',
            ])
            ->orderByDesc('archive_period')
            ->orderByDesc('id');

        // Search by name or phone
        if ($q !== null && $q !== '') {
            $query->where(function (Builder $builder) use ($q): void {
                $builder
                    ->where('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('normalized_phone', 'like', "%{$q}%");
            });
        }

        // Filter by archive period
        if ($archivePeriod !== null && $archivePeriod !== '') {
            $query->where('archive_period', $archivePeriod);
        }

        // Filter by leader
        if ($leaderId !== null && $leaderId > 0) {
            $query->where('leader_id', $leaderId);
        }

        // Filter by sub-leader
        if ($subLeaderId !== null && $subLeaderId > 0) {
            $query->where('sub_leader_id', $subLeaderId);
        }

        $histories = $query->paginate($perPage)->withQueryString();

        // Build dropdown options
        $leaders = User::where('role', User::ROLE_LEADER)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subLeaders = User::where('role', User::ROLE_SUB_LEADER)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Distinct archive periods for the filter dropdown
        $archivePeriods = ContactHistory::query()
            ->select('archive_period')
            ->distinct()
            ->orderByDesc('archive_period')
            ->pluck('archive_period');

        return view('superadmin.contact-histories.index', [
            'histories'      => $histories,
            'leaders'        => $leaders,
            'subLeaders'     => $subLeaders,
            'archivePeriods' => $archivePeriods,
            'filters'        => [
                'q'              => $q,
                'archive_period' => $archivePeriod,
                'leader_id'      => $leaderId,
                'sub_leader_id'  => $subLeaderId,
                'per_page'       => $perPage,
            ],
        ]);
    }
}
