<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;

class LandingController extends Controller
{
    public function index()
    {
        $deals = Product::active()
            ->whereNotNull('discount_price')
            ->with(['images', 'seller.sellerProfile'])
            ->latest()
            ->take(6)
            ->get();

        if ($deals->count() < 6) {
            $deals = $deals->concat(
                Product::active()
                    ->whereNotIn('id', $deals->pluck('id'))
                    ->with(['images', 'seller.sellerProfile'])
                    ->latest()
                    ->take(6 - $deals->count())
                    ->get()
            );
        }

        $stats = [
            'sellers' => User::where('role', 'seller')->count(),
            'riders' => User::where('role', 'courier')->count(),
            'products' => Product::active()->count(),
        ];

        return view('landing.index', compact('deals', 'stats'));
    }
}
