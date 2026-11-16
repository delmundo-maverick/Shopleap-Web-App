<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\TransferRequest;
use App\Models\ShipmentBag;
use App\Services\CompanyTransfers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(TransferRequest $request, CompanyTransfers $transfers): View
    {
        $company = $request->user()->company;
        $bags = $transfers->filtered($company, $request->validated())->with(['fromHub:id,business_name', 'toHub:id,business_name'])
            ->withCount(['items', 'items as unreceived_count' => fn (Builder $items) => $items->whereNull('received_at')])
            ->latest('created_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('company.transfers.index', [
            'bags' => $bags, 'hubs' => $company->hubs()->select(['public_code', 'business_name'])->orderBy('business_name')->get(),
            'statuses' => ShipmentBag::STATUS_LABELS, 'lateBefore' => now()->subHours(48),
        ]);
    }

    public function show(Request $request, ShipmentBag $bag, CompanyTransfers $transfers): View
    {
        abort_unless($transfers->bags($request->user()->company)->whereKey($bag->id)->exists(), 403);
        $bag->load(['fromHub:id,business_name,province,municipality', 'toHub:id,business_name,province,municipality'])->loadCount(['items', 'items as unreceived_count' => fn (Builder $items) => $items->whereNull('received_at')]);
        $items = $bag->items()->with('order:id,order_number,status,ship_municipality,ship_province')->orderBy('id')->paginate(30);

        return view('company.transfers.show', ['bag' => $bag, 'items' => $items, 'lateBefore' => now()->subHours(48)]);
    }
}
