<?php

namespace App\Http\Controllers;

use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog): View
    {
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
