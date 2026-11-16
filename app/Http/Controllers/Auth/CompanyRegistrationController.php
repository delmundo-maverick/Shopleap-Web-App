<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesOtpRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Services\OtpService;
use App\Support\PolicyAcceptance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanyRegistrationController extends Controller
{
    use HandlesOtpRegistration;

    public function choose(): View
    {
        return view('company.choose-registration');
    }

    public function create(): View
    {
        return view('company.register');
    }

    public function store(RegisterCompanyRequest $request, OtpService $otpService): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        return $this->stashForOtpVerification($request, 'logistics_company', [
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
            'password' => Hash::make($data['password']), 'role' => 'logistics_company',
            'account_status' => 'active', ...PolicyAcceptance::attributes(),
        ], [
            'name' => $data['company_name'], 'address' => $data['address'],
            'contact_phone' => $data['contact_phone'], 'contact_email' => $data['contact_email'],
            'logo_path' => $request->file('logo')?->store('company-logos', 'public'), 'status' => 'pending',
        ], $otpService);
    }
}
