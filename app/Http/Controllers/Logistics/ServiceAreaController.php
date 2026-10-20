<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceAreaController extends Controller
{
    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $areas = $hub->serviceAreas()
            ->with(['rider', 'barangays.rider'])
            ->orderBy('province')->orderBy('municipality')->get();

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

    /** Assign (or clear, with no rider chosen) one rider for the ticked barangays of this municipality. */
    public function updateBarangays(Request $request, HubServiceArea $area)
    {
        $hub = $request->user()->logisticsProfile;
        abort_unless($area->logistics_profile_id === $hub->id, 403);

        $validated = $request->validate([
            'barangays' => ['required', 'array', 'min:1', 'max:300'],
            'barangays.*' => ['required', 'string', 'max:150', 'not_regex:/^\d+$/'],
            'rider_id' => ['nullable', 'integer'],
        ], [
            'barangays.required' => 'Tick at least one barangay first.',
            'barangays.min' => 'Tick at least one barangay first.',
        ]);

        $riderId = $this->resolveRiderId($hub->id, $validated['rider_id'] ?? null);

        $existing = $area->barangays()->get()->keyBy(fn($b) => HubServiceArea::barangayKey($b->barangay));
        $names = collect($validated['barangays'])->unique(fn($n) => HubServiceArea::barangayKey($n))->values();

        DB::transaction(function () use ($area, $names, $existing, $riderId) {
            foreach ($names as $name) {
                $row = $existing->get(HubServiceArea::barangayKey($name));

                if ($riderId === null) {
                    $row?->delete();
                    continue;
                }

                if ($row) {
                    $row->update(['rider_id' => $riderId]);
                } else {
                    $area->barangays()->create(['barangay' => trim($name), 'rider_id' => $riderId]);
                }
            }
        });

        $message = $riderId === null
            ? "{$names->count()} barangay(s) in {$area->municipality} cleared."
            : "{$names->count()} barangay(s) in {$area->municipality} assigned.";

        return back()->with('status', $message);
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
