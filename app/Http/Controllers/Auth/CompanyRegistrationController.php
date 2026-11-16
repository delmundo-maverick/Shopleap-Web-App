<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesOtpRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Models\Company;
use App\Services\BuyerAddressLocations;
use App\Services\OtpService;
use App\Support\PolicyAcceptance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CompanyRegistrationController extends Controller
{
    use HandlesOtpRegistration;

    public function choose(): RedirectResponse
    {
        return redirect()->route('company.register');
    }

    public function create(): View
    {
        return view('company.register');
    }

    public function locations(Request $request, BuyerAddressLocations $locations): JsonResponse
    {
        $data = $request->validate(['level' => ['required', 'in:province,municipality,barangay'], 'code' => ['nullable', 'required_unless:level,province', 'regex:/^[0-9]{9,10}$/']]);

        return response()->json($locations->places($data['level'], $data['code'] ?? null));
    }

    public function store(RegisterCompanyRequest $request, OtpService $otpService, BuyerAddressLocations $locations): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $location = $locations->normalize(['province' => $data['province'], 'city_municipality' => $data['municipality'], 'barangay' => $data['barangay']]);
        $name = trim($data['first_name'].' '.$data['last_name']);
        $documents = [];
        $logoPath = null;

        try {
            foreach (Company::DOCUMENTS as $key => $document) {
                $path = $request->file($document['input'])->store('company_uploads/'.$key, 'r2');
                if (! $path) {
                    throw ValidationException::withMessages([$document['input'] => 'This document could not be uploaded. Please try again.']);
                }
                $documents[$document['path']] = $path;
            }
            $logoPath = $request->file('logo')?->store('company-logos', 'public');
            if ($request->hasFile('logo') && ! $logoPath) {
                throw ValidationException::withMessages(['logo' => 'The company logo could not be uploaded. Please try again.']);
            }

            return $this->stashForOtpVerification($request, 'logistics_company', [
                'name' => $name, 'email' => $data['email'], 'phone' => $data['contact_no'],
                'password' => Hash::make($data['password']), 'role' => 'logistics_company',
                'account_status' => 'active', ...PolicyAcceptance::attributes(),
            ], [
                'name' => $data['company_name'],
                'address' => implode(', ', [$data['street_address'], $location['barangay'], $location['city_municipality'], $location['province']]),
                'province' => $location['province'], 'municipality' => $location['city_municipality'],
                'barangay' => $location['barangay'], 'street_address' => $data['street_address'],
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'middle_initial' => $data['middle_initial'] ?? null,
                'sex' => $data['sex'], 'birthday' => $data['birthday'], 'age' => Carbon::parse($data['birthday'])->age,
                'service_regions' => $data['service_regions'],
                'contact_phone' => $data['contact_no'], 'contact_email' => $data['email'],
                'logo_path' => $logoPath, 'status' => 'pending', ...$documents,
            ], $otpService);
        } catch (Throwable $exception) {
            try {
                if ($documents !== []) {
                    Storage::disk('r2')->delete(array_values($documents));
                }
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }
}
