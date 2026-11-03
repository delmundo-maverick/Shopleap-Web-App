<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\HubServiceArea;
use App\Services\ActivityLogger;
use App\Services\HubContext;
use App\Services\HubServiceAreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceAreaController extends Controller
{
    public function __construct(
        private HubServiceAreaService $areas,
    ) {}

    public function index(Request $request): View
    {
        $hub = HubContext::require($request);

        $locationKnown = $this->areas->hubLocationIsKnown($hub);

        return view('logistics.areas.index', [
            'hub' => $hub,
            'barangays' => $locationKnown ? $this->areas->barangayStatus($hub) : [],
            'riders' => $this->areas->hubRiders($hub),
            'otherAreas' => $this->areas->otherAreas($hub),
            'locationKnown' => $locationKnown,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $hub = HubContext::require($request);

        $barangays = $request->input('barangays', []);
        $riders = $request->input('riders', []);

        $this->areas->claimBarangays($hub, [
            'barangays' => is_array($barangays) ? $barangays : [],
            'riders' => is_array($riders) ? $riders : [],
        ]);

        $message = "Service areas saved for {$hub->municipality}, {$hub->province}.";

        ActivityLogger::log(
            'area.barangays_saved',
            'Logistics',
            "Saved service barangays for {$hub->municipality}, {$hub->province}",
            $hub
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, HubServiceArea $area): RedirectResponse
    {
        $hub = HubContext::require($request);
        abort_unless((int) $area->logistics_profile_id === (int) $hub->id, 403);

        $homeKey = HubServiceArea::keyFor($hub->province, $hub->municipality);
        abort_if($area->area_key === $homeKey, 403, 'The home area cannot be deleted.');

        $areaName = $area->municipality;
        $areaProvince = $area->province;

        $this->areas->deleteArea($hub, $area);

        ActivityLogger::log(
            'area.deleted',
            'Logistics',
            "Removed service area {$areaName} ({$areaProvince})",
            $area
        );

        return back()->with('status', "{$areaName} removed from your service areas.");
    }
}
