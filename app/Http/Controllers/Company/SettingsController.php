<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateSettingsRequest;
use App\Models\Company;
use App\Models\ShippingRate;
use App\Services\CompanyActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('company.settings', ['company' => $request->user()->company]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['default_rider_rate'] ??= null;
        $path = $request->file('logo')?->store('company-logos', 'public');
        try {
            DB::transaction(function () use ($request, $data, $path): void {
                $company = Company::whereKey($request->user()->company_id)->lockForUpdate()->firstOrFail();
                if ($data['default_rider_rate'] !== null) {
                    $lowest = ShippingRate::query()->orderBy('id')->sharedLock()->get()->min('fee');
                    if ($lowest === null || (float) $data['default_rider_rate'] >= (float) $lowest) {
                        throw ValidationException::withMessages(['default_rider_rate' => 'The suggested rate must be lower than the lowest configured shipping fee.']);
                    }
                }
                $oldPath = $company->logo_path;
                $company->fill($data + ($path ? ['logo_path' => $path] : []));
                $fields = array_keys($company->getDirty());
                $company->save();
                CompanyActivity::record($company->id, 'settings.updated', $company, ['fields' => $fields]);
                if ($path && $oldPath) {
                    DB::afterCommit(function () use ($oldPath): void {
                        if (! Company::where('logo_path', $oldPath)->exists()) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    });
                }
            });
        } catch (\Throwable $exception) {
            if ($path && ! Company::where('logo_path', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return back()->with('status', 'Company settings updated. Existing hub rider rates are unchanged.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', ['current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password']]);
        DB::transaction(function () use ($request, $data): void {
            $request->user()->update(['password' => $data['password']]);
            CompanyActivity::record($request->user()->company_id, 'settings.password_changed', $request->user());
        });
        $request->session()->regenerate();

        return back()->with('status', 'Company account password changed.');
    }
}
