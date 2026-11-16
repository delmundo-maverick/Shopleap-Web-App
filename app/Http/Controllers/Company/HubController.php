<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SaveHubRequest;
use App\Models\LogisticsProfile;
use App\Services\BuyerAddressLocations;
use App\Services\CompanyHubs;
use App\Services\HubServiceAreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubController extends Controller
{
    public function index(Request $request): View
    {
        return view('company.hubs.index', ['hubs' => $request->user()->company->hubs()->with('user')->withCount('riders')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('company.hubs.form', ['hub' => null, 'selectedBarangays' => []]);
    }

    public function edit(Request $request, LogisticsProfile $hub): View
    {
        $this->authorizeHub($request, $hub);
        $hub->load(['user', 'serviceAreas.barangays']);

        return view('company.hubs.form', ['hub' => $hub, 'selectedBarangays' => $hub->serviceAreas->flatMap->barangays->pluck('barangay')->all()]);
    }

    public function store(SaveHubRequest $request, CompanyHubs $hubs, BuyerAddressLocations $locations): RedirectResponse
    {
        $hubs->save($request->user()->company, $this->validatedHubData($request, $locations));

        return redirect()->route('company.hubs.index')->with('status', 'Hub created. A password invitation will be emailed to the manager.');
    }

    public function update(SaveHubRequest $request, LogisticsProfile $hub, CompanyHubs $hubs, BuyerAddressLocations $locations): RedirectResponse
    {
        $this->authorizeHub($request, $hub);
        $hubs->save($request->user()->company, $this->validatedHubData($request, $locations), $hub);

        return redirect()->route('company.hubs.index')->with('status', 'Hub updated. If the manager email changed, a new invitation will be sent.');
    }

    public function suspend(Request $request, LogisticsProfile $hub, CompanyHubs $hubs): RedirectResponse
    {
        $this->authorizeHub($request, $hub);
        $hubs->setSuspended($hub, true);

        return back()->with('status', 'Hub suspended. The manager can no longer sign in.');
    }

    public function reactivate(Request $request, LogisticsProfile $hub, CompanyHubs $hubs): RedirectResponse
    {
        $this->authorizeHub($request, $hub);
        $hubs->setSuspended($hub, false);

        return back()->with('status', 'Hub reactivated.');
    }

    public function invite(Request $request, LogisticsProfile $hub, CompanyHubs $hubs): RedirectResponse
    {
        $this->authorizeHub($request, $hub);
        $hubs->invite($hub);

        return back()->with('status', 'A new invitation will be sent. The previous link has expired.');
    }

    public function locations(Request $request, BuyerAddressLocations $locations): JsonResponse
    {
        $data = $request->validate(['level' => ['required', 'in:province,municipality,barangay'], 'code' => ['nullable', 'required_unless:level,province', 'regex:/^[0-9]{9,10}$/']]);

        return response()->json($locations->places($data['level'], $data['code'] ?? null));
    }

    public function barangays(Request $request, HubServiceAreaService $areas): JsonResponse
    {
        $data = $request->validate(['province' => ['required', 'string', 'max:255'], 'municipality' => ['required', 'string', 'max:255'], 'hub' => ['nullable', 'uuid']]);
        $hub = isset($data['hub']) ? LogisticsProfile::where('public_code', $data['hub'])->firstOrFail() : new LogisticsProfile;
        if ($hub->exists) {
            $this->authorizeHub($request, $hub);
        }
        $hub->fill(['province' => $data['province'], 'municipality' => $data['municipality']]);
        if (! $areas->ensureBarangayData($hub)) {
            return response()->json(['message' => 'Barangays could not be loaded. Please try again.'], 422);
        }

        return response()->json(collect($areas->barangayStatus($hub))->where('status', '!=', 'taken')->values());
    }

    /** @return array<string, mixed> */
    private function validatedHubData(SaveHubRequest $request, BuyerAddressLocations $locations): array
    {
        $data = $request->validated();
        $location = $locations->normalize(['province' => $data['province'], 'city_municipality' => $data['municipality'], 'barangay' => $data['barangay']]);

        return array_replace($data, ['province' => $location['province'], 'municipality' => $location['city_municipality'],
            'barangay' => $location['barangay'], 'contact_no' => $data['manager_phone']]);
    }

    private function authorizeHub(Request $request, LogisticsProfile $hub): void
    {
        abort_unless($hub->company_id !== null && (int) $hub->company_id === (int) $request->user()->company_id, 403);
    }
}
