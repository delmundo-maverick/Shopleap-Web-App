<?php

namespace App\Http\Controllers;

use App\Services\PublicCatalog;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog): View|RedirectResponse
    {
        if (Portal::isLogistics($request)) {
            if ($request->user()?->role === 'logistics_company') {
                return redirect()->route('company.dashboard');
            }

            if ($request->user()?->role === 'logistics') {
                return redirect()->route('logistics.dashboard');
            }

            return view('logistics.landing');
        }

        if ($request->getHost() === Portal::host('admin') && Portal::host('admin') !== Portal::host('main')) {
            return redirect()->route($request->user() ? 'admin.dashboard' : 'login');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        return view('landing.index', [
            'categories' => config('shopleap_categories.list', []),
            'bestSellers' => $catalog->featured(),
            'recommended' => $catalog->discover($filters['search'] ?? '', $filters['category'] ?? ''),
        ]);
    }
}
