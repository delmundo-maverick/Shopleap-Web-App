<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\DashboardRequest;
use App\Services\HubContext;
use App\Services\LogisticsDashboardService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request, LogisticsDashboardService $dashboard): View
    {
        $hub = HubContext::require($request);
        $data = $request->validated();

        return view('logistics.dashboard', $dashboard->build($hub, $data['range'] ?? '7d', $data['queue'] ?? 'all'));
    }
}
