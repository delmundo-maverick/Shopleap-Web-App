<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\ParcelEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubMapController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        $handled = ParcelEvent::whereIn('hub_id', $company->hubs()->select('id'))
            ->where('event_type', '!=', ParcelEvent::TYPE_STALE)
            ->where('created_at', '>=', now('Asia/Manila')->startOfDay()->setTimezone(config('app.timezone')))
            ->where('created_at', '<=', now())
            ->select('hub_id')->selectRaw('COUNT(DISTINCT order_id) AS total')->groupBy('hub_id')->pluck('total', 'hub_id');
        $mapHubs = $company->hubs()->with('user:id,name,account_status')->withCount('riders')->orderBy('business_name')->get()
            ->map(fn (LogisticsProfile $hub): array => [
                'code' => $hub->public_code, 'name' => $hub->business_name,
                'province' => $hub->province, 'municipality' => $hub->municipality,
                'manager' => $hub->user->name, 'status' => $hub->isAvailable() ? 'active' : 'suspended',
                'latitude' => $hub->latitude === null ? null : (float) $hub->latitude,
                'longitude' => $hub->longitude === null ? null : (float) $hub->longitude,
                'riders' => $hub->riders_count, 'handledToday' => (int) ($handled[$hub->id] ?? 0),
                'editUrl' => route('company.hubs.edit', $hub->public_code),
            ])->values()->all();

        return view('company.map', compact('mapHubs'));
    }
}
