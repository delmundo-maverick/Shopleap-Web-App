<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreRiderRateRequest;
use App\Models\LogisticsProfile;
use App\Models\RiderRate;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\HubContext;
use App\Services\RiderEarnings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RiderRateController extends Controller
{
    public function index(Request $request, RiderEarnings $earnings): View
    {
        $hub = HubContext::require($request);
        $history = RiderRate::where('logistics_profile_id', $hub->id)->orderByDesc('effective_from')->orderByDesc('id')->paginate(15);
        $creators = User::whereIn('id', $history->pluck('created_by'))->pluck('name', 'id');

        return view('logistics.rider-rate', ['currentRate' => $earnings->currentRate($hub->id),
            'lowestFee' => $earnings->lowestShippingFee(), 'history' => $history, 'creators' => $creators]);
    }

    public function store(StoreRiderRateRequest $request): RedirectResponse
    {
        $hub = HubContext::require($request);
        $amount = $request->validated('amount');
        DB::transaction(function () use ($hub, $amount, $request): void {
            LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $rates = ShippingRate::query()->orderBy('id')->sharedLock()->get();
            $lowest = $rates->min('fee');
            if ($lowest === null || (float) $amount >= (float) $lowest) {
                throw ValidationException::withMessages(['amount' => $lowest === null
                    ? 'An admin must configure shipping fees before you can set a rider rate.'
                    : 'The rider rate must be lower than the lowest shipping fee (PHP '.RiderEarnings::money($lowest).').']);
            }
            RiderRate::create(['logistics_profile_id' => $hub->id, 'amount' => $amount,
                'effective_from' => now(), 'created_by' => $request->user()->id]);
        });

        return back()->with('status', 'Rider rate saved. It applies to future successful deliveries.');
    }
}
