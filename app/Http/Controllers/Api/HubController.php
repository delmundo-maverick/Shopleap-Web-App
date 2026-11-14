<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Services\HubBarangayCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HubController extends Controller
{
    /** Approved, active sorting centers a rider can apply to. Optional ?q= searches name, company or municipality. */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = trim($validated['q'] ?? '');

        $hubs = Cache::remember('public-hubs:'.hash('sha256', $q), 60, fn (): array => LogisticsProfile::available()
            ->with('serviceAreas')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('business_name', 'ilike', "%{$q}%")
                    ->orWhere('company_name', 'ilike', "%{$q}%")
                    ->orWhere('municipality', 'ilike', "%{$q}%")
                    ->orWhereHas('serviceAreas', fn ($a) => $a->where('municipality', 'ilike', "%{$q}%"));
            }))
            ->orderBy('company_name')->orderBy('business_name')
            ->get()
            ->map(fn ($hub) => [
                'id' => $hub->id,
                'name' => $hub->business_name,
                'company' => $hub->company_name,
                'municipality' => $hub->municipality,
                'province' => $hub->province,
                'service_areas' => $hub->serviceAreas->pluck('municipality')->values()->all(),
            ])->all());

        return response()->json(['data' => $hubs]);
    }

    /** The barangays inside a hub's service areas. Riders must choose one of these. */
    public function barangays(LogisticsProfile $hub)
    {
        abort_unless(
            LogisticsProfile::available()->whereKey($hub->id)->exists(),
            404,
            'That sorting center is not available.'
        );

        return response()->json(['data' => HubBarangayCatalog::forHub($hub)]);
    }
}
