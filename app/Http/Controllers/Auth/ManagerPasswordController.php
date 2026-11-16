<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\User;
use App\Services\CompanyManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ManagerPasswordController extends Controller
{
    public function show(Request $request, LogisticsProfile $hub, CompanyManagers $managers): View
    {
        $this->validateLink($request, $hub, $managers);

        return view('company.manager-password', compact('hub'));
    }

    public function store(Request $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        DB::transaction(function () use ($request, $hub, $managers, $data): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $hub->setRelation('user', User::whereKey($hub->user_id)->lockForUpdate()->firstOrFail());
            $this->validateLink($request, $hub, $managers);
            $hub->user->forceFill(['password' => Hash::make($data['password'])])->save();
            $managers->revokeSessions($hub->user);
        });

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in again.');
    }

    private function validateLink(Request $request, LogisticsProfile $hub, CompanyManagers $managers): void
    {
        abort_unless($hub->company_id !== null && $hub->manager_password_set_at !== null && $hub->user?->role === 'logistics'
            && hash_equals($managers->credential($hub->user), (string) $request->query('credential')), 403, 'This password reset link is no longer valid. Ask your company for another link.');
    }
}
