<?php

namespace App\Http\Controllers;

use App\Services\DashboardRecapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardRecapService $dashboardRecapService)
    {
    }

    public function __invoke(Request $request): View
    {
        $user = auth()->user();
        $dashboardData = $this->dashboardRecapService->getDashboardData($user, $request);

        return view('dashboard', array_merge(['user' => $user], $dashboardData));
    }
}
