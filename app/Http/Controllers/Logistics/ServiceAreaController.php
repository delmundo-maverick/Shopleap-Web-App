<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use Illuminate\Http\Request;

class ServiceAreaController extends Controller
{
    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $areas = $hub->serviceAreas()->with('rider')->orderBy('province')->orderBy('municipality')->get();
        $riders = CourierProfile::where('logistics_profile_id', $hub->id)
            ->where('status', 'approved')->orderBy('first_name')->get();

        return view('logistics.areas.index', compact('hub', 'areas', 'riders'));
    }

    public function store(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $validated = $request->validate([
            'province' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'zone_name' => ['nullable', 'string', 'max:50'],
            'rider_id' => ['nullable', 'integer'],
        ]);

        $key = HubServiceArea::keyFor($validated['province'], $validated['municipality']);

        if ($hub->serviceAreas()->get()->contains(fn($a) => $a->area_key === $key)) {
            return back()->withErrors(['municipality' => 'That municipality is already in your service areas.'])->withInput();
        }

        $hub->serviceAreas()->create([
            'province' => $validated['province'],
            'municipality' => $validated['municipality'],
            'zone_name' => $validated['zone_name'] ?? null,
            'rider_id' => $this->resolveRiderId($hub->id, $validated['rider_id'] ?? null),
        ]);

        return back()->with('status', "{$validated['municipality']} added to your service areas.");
    }

    public function update(Request $request, HubServiceArea $area)
    {
        $hub = $request->user()->logisticsProfile;
        abort_unless($area->logistics_profile_id === $hub->id, 403);

        $validated = $request->validate([
            'zone_name' => ['nullable', 'string', 'max:50'],
            'rider_id' => ['nullable', 'integer'],
        ]);

        $area->update([
            'zone_name' => $validated['zone_name'] ?? null,
            'rider_id' => $this->resolveRiderId($hub->id, $validated['rider_id'] ?? null),
        ]);

        return back()->with('status', "{$area->municipality} updated.");
    }

    public function destroy(Request $request, HubServiceArea $area)
    {
        abort_unless($area->logistics_profile_id === $request->user()->logisticsProfile->id, 403);

        $area->delete();

        return back()->with('status', "{$area->municipality} removed from your service areas.");
    }

    private function resolveRiderId(int $hubId, ?int $riderId): ?int
    {
        if (! $riderId) {
            return null;
        }

        return CourierProfile::where('logistics_profile_id', $hubId)
            ->where('status', 'approved')
            ->findOrFail($riderId)->id;
    }
}
