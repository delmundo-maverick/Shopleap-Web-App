<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\ShipmentBag;
use Illuminate\Http\Request;

class HubAdminController extends Controller
{
    /** Hubs grouped by normalized company name. */
    public function companies(Request $request)
    {
        $hubs = LogisticsProfile::with(['user', 'serviceAreas.barangays'])
            ->orderBy('company_name')
            ->orderBy('business_name')
            ->get()
            ->groupBy(fn ($hub) => $hub->companyKey());

        return view('admin.hubs.companies', compact('hubs'));
    }

    /** Barangays owned by each hub. */
    public function barangays(Request $request)
    {
        $hubs = LogisticsProfile::with(['serviceAreas.barangays.rider'])
            ->orderBy('company_name')
            ->orderBy('business_name')
            ->get();

        return view('admin.hubs.barangays', compact('hubs'));
    }

    /** Bag/transfer overview across all hubs. */
    public function transfers(Request $request)
    {
        $bags = ShipmentBag::with(['fromHub', 'toHub', 'creator'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'open' => ShipmentBag::where('status', ShipmentBag::STATUS_OPEN)->count(),
            'sealed' => ShipmentBag::where('status', ShipmentBag::STATUS_SEALED)->count(),
            'in_transit' => ShipmentBag::where('status', ShipmentBag::STATUS_IN_TRANSIT)->count(),
            'received' => ShipmentBag::where('status', ShipmentBag::STATUS_RECEIVED)->count(),
            'discrepancy' => ShipmentBag::where('status', ShipmentBag::STATUS_DISCREPANCY)->count(),
        ];

        return view('admin.hubs.transfers', compact('bags', 'counts'));
    }
}
