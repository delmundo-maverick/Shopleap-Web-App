<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CoverageRequest;
use App\Models\HubServiceArea;
use App\Services\CompanyCoverage;
use Illuminate\View\View;

class CoverageController extends Controller
{
    public function index(CoverageRequest $request, CompanyCoverage $coverage): View
    {
        $company = $request->user()->company;
        $filters = $request->validated();
        $places = $company->hubs()->select(['province', 'municipality'])->distinct()->orderBy('province')->orderBy('municipality')->get();
        $rows = $coverage->listing($company, $filters)->orderBy('places.province')->orderBy('places.municipality')->orderBy('places.barangay')->paginate(30)->withQueryString();

        return view('company.coverage', [
            'rows' => $rows, 'places' => $places, 'hasHubs' => $places->isNotEmpty(),
            'provinces' => $places->pluck('province')->unique()->values(),
            'municipalities' => $places->when($filters['province'] ?? null, fn ($items, $province) => $items->filter(fn ($place) => HubServiceArea::nameKey($place->province) === HubServiceArea::nameKey($province)))->pluck('municipality')->unique()->values(),
            'missingReference' => $coverage->hasMissingReference($company),
        ]);
    }
}
