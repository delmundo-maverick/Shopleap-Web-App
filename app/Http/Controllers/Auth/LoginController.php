<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Gate: block login for roles awaiting admin approval
        $profile = match ($user->role) {
            'seller' => $user->sellerProfile,
            'buyer' => $user->buyerProfile,
            // 'logistics' => $user->logisticsProfile, // add once logistics_profiles exists
            default => null,
        };

        if ($profile && $profile->status !== 'approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $profile->status === 'rejected'
                ? 'Your registration was not approved. Please contact support for details.'
                : 'Your registration is still pending administrator approval. You\'ll be notified by email once reviewed.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        if ($user->account_status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->account_status === 'suspended'
                ? 'Your account has been suspended. Please contact support.'
                : 'Your account has been deactivated.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $request->session()->regenerate();

        return match ($user->role) {
            'super_admin' => redirect()->intended(route('admin.dashboard')),
            'seller' => redirect()->intended(route('seller.dashboard')),
            'buyer' => redirect()->intended(route('buyer.home')),
            default => redirect()->intended(route('home')),
        };
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
