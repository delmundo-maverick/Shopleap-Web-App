<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use Illuminate\Http\Request;

class HubController extends Controller
{
    /** Approved, active sorting centers a rider can apply to. Optional ?q= searches name, company or municipality. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $hubs = LogisticsProfile::available()
            ->with('serviceAreas')
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('business_name', 'ilike', "%{$q}%")
                    ->orWhere('company_name', 'ilike', "%{$q}%")
                    ->orWhere('municipality', 'ilike', "%{$q}%")
                    ->orWhereHas('serviceAreas', fn($a) => $a->where('municipality', 'ilike', "%{$q}%"));
            }))
            ->orderBy('company_name')->orderBy('business_name')
            ->get()
            ->map(fn($hub) => [
                'id' => $hub->id,
                'name' => $hub->business_name,
                'company' => $hub->company_name,
                'municipality' => $hub->municipality,
                'province' => $hub->province,
                'service_areas' => $hub->serviceAreas->pluck('municipality')->values(),
            ]);

        return response()->json(['data' => $hubs]);
    }
}
