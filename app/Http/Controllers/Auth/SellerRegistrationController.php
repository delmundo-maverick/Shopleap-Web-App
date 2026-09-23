<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SellerRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register.seller');
    }

    public function store(Request $request, OtpService $otpService)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:male,female'],
            'email' => ['required', 'email', 'unique:users,email'],
            'contact_no' => ['required', 'string', 'max:20'],
            'birthday' => ['required', 'date', 'before:today'],
            'province' => ['required', 'string'],
            'municipality' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'street_address' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:150'],
            'line_of_business' => ['required', 'string', 'max:100'],
            'id_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Server-side age calculation — never trust client-computed age
        $age = Carbon::parse($validated['birthday'])->age;

        // Uploaded now (files don't survive past this request); DB rows wait until OTP is verified.
        $idPath = $request->file('id_upload')->store('seller_uploads/ids', 'r2');
        $permitPath = $request->file('business_permit')->store('seller_uploads/permits', 'r2');

        session([
            'pending_registration' => [
                'role' => 'seller',
                'email' => $validated['email'],
                'user' => [
                    'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'seller',
                    'account_status' => 'active',
                ],
                'profile' => [
                    'last_name' => $validated['last_name'],
                    'first_name' => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'] ?? null,
                    'sex' => $validated['sex'],
                    'contact_no' => $validated['contact_no'],
                    'birthday' => $validated['birthday'],
                    'age' => $age,
                    'province' => $validated['province'],
                    'municipality' => $validated['municipality'],
                    'barangay' => $validated['barangay'],
                    'street_address' => $validated['street_address'],
                    'business_name' => $validated['business_name'],
                    'line_of_business' => $validated['line_of_business'],
                    'id_upload_path' => $idPath,
                    'business_permit_path' => $permitPath,
                    'status' => 'pending',
                ],
            ],
        ]);

        $otpService->generateAndSend($validated['email'], 'seller');

        if ($request->wantsJson()) {
            return response()->json(['email' => $validated['email']]);
        }

        return redirect()->route('register.otp.form', 'seller')
            ->with('status', 'We sent a verification code to your email.');
    }
}
