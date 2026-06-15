<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactFilter;
use App\Services\LeaderSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private LeaderSummaryService $leaderSummaryService)
    {
    }

    public function index(Request $request): View
    {
        $filters = ContactFilter::resolveDateFilter($request);
        $uiFilters = ContactFilter::resolveUiFilters($request);
        $selectedLeaderId = $request->integer('leader_id');

        $leaders = User::where('role', User::ROLE_LEADER)
            ->withCount('subLeaders')
            ->orderBy('id')
            ->get();

        $this->leaderSummaryService->populateSummaries($leaders, $filters, $uiFilters);

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
        ContactFilter::applyDateFilter($contactsQuery, $filters);
        ContactFilter::applyListFilters($contactsQuery, $uiFilters);

        if ($selectedLeaderId > 0) {
            $selectedLeader = $leaders->firstWhere('id', $selectedLeaderId);
            if ($selectedLeader && $selectedLeader->team_id) {
                $contactsQuery->where('team_id', $selectedLeader->team_id);
            } else {
                $contactsQuery->whereRaw('1 = 0');
            }
        }

        return view('superadmin.contacts.index', [
            'leaders' => $leaders,
            'summaryLeaders' => $summaryLeaders,
            'leaderNumberMap' => $leaderNumberMap,
            'selectedLeaderId' => $selectedLeaderId > 0 ? $selectedLeaderId : null,
            'filters' => $filters,
            'uiFilters' => $uiFilters,
            'contacts' => $contactsQuery->paginate((int) $uiFilters['per_page'])->withQueryString(),
        ]);
    }
}
