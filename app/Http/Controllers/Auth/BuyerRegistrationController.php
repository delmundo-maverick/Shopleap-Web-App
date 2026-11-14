<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesOtpRegistration;
use App\Http\Controllers\Controller;
use App\Rules\UniqueRoleEmail;
use App\Services\OtpService;
use App\Support\PolicyAcceptance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BuyerRegistrationController extends Controller
{
    use HandlesOtpRegistration;

    public function create()
    {
        return view('auth.register.buyer');
    }

    public function store(Request $request, OtpService $otpService)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', new UniqueRoleEmail('buyer')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:male,female'],
            'birthday' => ['required', 'date', 'before:today'],
            'contact_no' => ['required', 'string', 'max:20'],
            // Names only: a value made of digits is a PSGC code from a stale script.
            'province' => ['required', 'string', 'not_regex:/^\d+$/'],
            'municipality' => ['required', 'string', 'not_regex:/^\d+$/'],
            'barangay' => ['required', 'string', 'not_regex:/^\d+$/'],
            'house_number' => ['required', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:150'],
            'additional_address' => ['nullable', 'string', 'max:255'],
            'id_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'terms_accepted' => ['required', 'accepted'],
        ], [
            'province.not_regex' => 'Please select your province from the list.',
            'municipality.not_regex' => 'Please select your municipality from the list.',
            'barangay.not_regex' => 'Please select your barangay from the list.',
            'terms_accepted.required' => 'Please read and agree to the Terms and Conditions and Privacy Policy.',
            'terms_accepted.accepted' => 'Please read and agree to the Terms and Conditions and Privacy Policy.',
        ]);

        $age = Carbon::parse($validated['birthday'])->age;

        $idPath = $request->file('id_upload')->store('buyer_uploads/ids', 'r2');

        $userAttributes = [
            'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'buyer',
            'account_status' => 'active',
            ...PolicyAcceptance::attributes(),
        ];

        $profileAttributes = [
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
            'house_number' => $validated['house_number'],
            'street' => $validated['street'],
            'additional_address' => $validated['additional_address'] ?? null,
            'id_upload_path' => $idPath,
            'status' => 'pending',
        ];

        return $this->stashForOtpVerification($request, 'buyer', $userAttributes, $profileAttributes, $otpService);
    }
}
