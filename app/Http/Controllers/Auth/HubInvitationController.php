<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HubInvitationController extends Controller
{
    public function show(Request $request, LogisticsProfile $hub): View
    {
        $this->validateInvitation($request, $hub);

        return view('company.invitation', ['hub' => $hub]);
    }

    public function store(Request $request, LogisticsProfile $hub): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        DB::transaction(function () use ($request, $hub, $data): void {
            $hub = LogisticsProfile::whereKey($hub->id)->lockForUpdate()->firstOrFail();
            $this->validateInvitation($request, $hub);
            User::whereKey($hub->user_id)->lockForUpdate()->firstOrFail()->forceFill([
                'password' => Hash::make($data['password']), 'email_verified_at' => now(), 'remember_token' => null,
            ])->save();
            $hub->update(['manager_password_set_at' => now(), 'manager_invite_token' => null, 'manager_invite_expires_at' => null]);
        });

        return redirect()->route('login')->with('status', 'Password set. You can now sign in as the hub manager.');
    }

    private function validateInvitation(Request $request, LogisticsProfile $hub): void
    {
        abort_unless($hub->company_id !== null && $hub->manager_password_set_at === null
            && $hub->manager_invite_token && $hub->manager_invite_expires_at?->isFuture()
            && hash_equals($hub->manager_invite_token, (string) $request->query('token')), 403, 'This invitation has expired or was already used. Ask your company to resend it.');
    }
}
