<?php

namespace App\Http\Controllers;

use App\Models\PlatformPolicy;

class PolicyController extends Controller
{
    public function show(PlatformPolicy $policy)
    {
        return view('policies.show', compact('policy'));
    }
}
