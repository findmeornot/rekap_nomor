<?php

namespace App\Http\Controllers\SubLeader;

use App\Http\Controllers\Controller;
use App\Models\ContactHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $subLeader = auth()->user();

        $validated = $request->validate([
            'q'              => ['nullable', 'string', 'max:100'],
            'archive_period' => ['nullable', 'string', 'max:7', 'regex:/^\d{4}-\d{2}$/'],
            'per_page'       => ['nullable', 'in:10,20,50,100'],
        ]);

        $q             = isset($validated['q']) ? trim((string) $validated['q']) : null;
        $archivePeriod = $validated['archive_period'] ?? null;
        $perPage       = (int) ($validated['per_page'] ?? 20);

        // Sub-leader sees only contacts they personally inputted
        $query = ContactHistory::query()
            ->where('sub_leader_id', $subLeader->id)
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

        $histories = $query->paginate($perPage)->withQueryString();

        // Distinct archive periods for this sub-leader
        $archivePeriods = ContactHistory::query()
            ->where('sub_leader_id', $subLeader->id)
            ->select('archive_period')
            ->distinct()
            ->orderByDesc('archive_period')
            ->pluck('archive_period');

        return view('subleader.contact-histories.index', [
            'histories'      => $histories,
            'archivePeriods' => $archivePeriods,
            'filters'        => [
                'q'              => $q,
                'archive_period' => $archivePeriod,
                'per_page'       => $perPage,
            ],
        ]);
    }
}
