<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // TODO: replace with real counts once Courier (Phase 2) and
        // parcel operations (Phase 3) exist.
        $stats = [
            'pending_riders' => 0,
            'active_riders' => 0,
            'parcels_awaiting_sort' => 0,
            'parcels_out_for_delivery' => 0,
        ];

        return view('logistics.dashboard', compact('stats'));
    }
}
