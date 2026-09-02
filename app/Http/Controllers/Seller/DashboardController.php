<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sellerProfile = $request->user()->sellerProfile;

        return view('seller.dashboard', compact('sellerProfile'));
    }
}
