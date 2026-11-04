<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesOtpRegistration;
use App\Http\Controllers\Controller;
use App\Models\HubServiceArea;
use App\Models\LogisticsProfile;
use App\Services\OtpService;
use App\Support\PolicyAcceptance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LogisticsRegistrationController extends Controller
{
    use HandlesOtpRegistration;

    public function create()
    {
        return view('auth.register.logistics');
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
            'company_name' => ['required', 'string', 'max:150'],
            'id_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['required', 'accepted'],
        ], [
            'terms_accepted.required' => 'Please read and agree to the Terms and Conditions and Privacy Policy.',
            'terms_accepted.accepted' => 'Please read and agree to the Terms and Conditions and Privacy Policy.',
        ]);

        $age = Carbon::parse($validated['birthday'])->age;

        $idPath = $request->file('id_upload')->store('logistics_uploads/ids', 'r2');
        $permitPath = $request->file('business_permit')->store('logistics_uploads/permits', 'r2');

        $userAttributes = [
            'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'logistics',
            'account_status' => 'active',
            ...PolicyAcceptance::attributes(),
        ];

        $companyName = preg_replace('/\\s+/', ' ', trim($validated['company_name']));

        $joiningExisting = LogisticsProfile::query()
            ->get()
            ->contains(fn ($hub) => $hub->companyKey() === HubServiceArea::nameKey($companyName));

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
            'street_address' => $validated['street_address'],
            'business_name' => $validated['business_name'],
            'company_name' => $companyName,
            'joining_existing_company' => $joiningExisting,
            'id_upload_path' => $idPath,
            'business_permit_path' => $permitPath,
            'status' => 'pending',
        ];

        return $this->stashForOtpVerification($request, 'logistics', $userAttributes, $profileAttributes, $otpService);
    }
}
