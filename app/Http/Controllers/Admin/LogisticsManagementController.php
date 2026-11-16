<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LogisticsProfile;
use App\Notifications\LogisticsDecisionNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogisticsManagementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['company' => ['nullable', 'uuid']]);
        $query = LogisticsProfile::with(['user', 'company' => fn ($companies) => $companies->withCount('hubs')
            ->with(['users' => fn ($users) => $users->where('role', 'logistics_company')->select(['id', 'company_id', 'name', 'email', 'email_verified_at'])])])->latest();

        if ($request->filled('company')) {
            $query->whereHas('company', fn ($companies) => $companies->where('public_code', $request->input('company')));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('business_name', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%")
                    ->orWhereHas('company', fn ($companies) => $companies->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('company.users', fn ($accounts) => $accounts->where('role', 'logistics_company')->where('email', 'ilike', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('reg_status') && $request->reg_status !== 'all') {
            $query->where('status', $request->reg_status);
        }

        if ($request->filled('acc_status') && $request->acc_status !== 'all') {
            $query->whereHas('user', fn ($uq) => $uq->where('account_status', $request->acc_status));
        }

        $logisticsPartners = $query->paginate(15)->withQueryString();

        $companies = Company::select(['public_code', 'name'])->orderBy('name')->get();

        return view('admin.registrations.logistics.index', compact('logisticsPartners', 'companies'));
    }

    public function details(LogisticsProfile $logisticsProfile)
    {
        $logisticsProfile->load(['user', 'company' => fn ($companies) => $companies->withCount('hubs')
            ->with(['users' => fn ($users) => $users->where('role', 'logistics_company')->select(['id', 'company_id', 'name', 'email', 'email_verified_at'])])]);

        return response()->json([
            'id' => $logisticsProfile->id,
            'full_name' => trim("{$logisticsProfile->first_name} {$logisticsProfile->middle_initial} {$logisticsProfile->last_name}"),
            'sex' => $logisticsProfile->sex,
            'email' => $logisticsProfile->user->email,
            'contact_no' => $logisticsProfile->contact_no,
            'birthday' => $logisticsProfile->birthday?->format('M d, Y'),
            'age' => $logisticsProfile->age,
            'address' => "{$logisticsProfile->street_address}, {$logisticsProfile->barangay}, {$logisticsProfile->municipality}, {$logisticsProfile->province}",
            'business_name' => $logisticsProfile->business_name,
            'company_name' => $logisticsProfile->company?->name ?? $logisticsProfile->company_name,
            'company_linked' => $logisticsProfile->company !== null,
            'company_status' => $logisticsProfile->company?->status,
            'company_hub_count' => $logisticsProfile->company?->hubs_count ?? 0,
            'company_account_email' => $logisticsProfile->company?->users->first()?->email,
            'company_account_verified' => $logisticsProfile->company?->users->first()?->email_verified_at !== null,
            'company_url' => $logisticsProfile->company ? route('admin.companies.index', ['search' => $logisticsProfile->company->name]) : null,
            'service_regions' => $logisticsProfile->service_regions ?? [],
            'sec_url' => $logisticsProfile->sec_certificate_path ? Storage::disk('r2')->temporaryUrl($logisticsProfile->sec_certificate_path, now()->addMinutes(10)) : null,
            'bir_url' => $logisticsProfile->bir_registration_path ? Storage::disk('r2')->temporaryUrl($logisticsProfile->bir_registration_path, now()->addMinutes(10)) : null,
            'id_url' => $logisticsProfile->id_upload_path ? Storage::disk('r2')->temporaryUrl($logisticsProfile->id_upload_path, now()->addMinutes(10)) : null,
            'permit_url' => $logisticsProfile->business_permit_path ? Storage::disk('r2')->temporaryUrl($logisticsProfile->business_permit_path, now()->addMinutes(10)) : null,
            'registration_status' => $logisticsProfile->status,
            'rejection_reason' => $logisticsProfile->rejection_reason,
            'account_status' => $logisticsProfile->user->account_status,
            'submitted_at' => $logisticsProfile->created_at->format('M d, Y \a\t g:i A'),
        ]);
    }

    public function approve(LogisticsProfile $logisticsProfile): JsonResponse
    {
        $updated = LogisticsProfile::whereKey($logisticsProfile->id)->where('status', 'pending')->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        if ($updated !== 1) {
            return response()->json(['success' => false, 'message' => 'This application has already been reviewed.'], 422);
        }
        $logisticsProfile->refresh();

        ActivityLogger::log('logistics.approved', 'User Management', "Logistics registration approved: {$logisticsProfile->business_name}.", $logisticsProfile);

        $this->notifyDecision($logisticsProfile, true);

        return response()->json([
            'success' => true,
            'message' => "{$logisticsProfile->business_name} has been approved.",
            'registration_status' => 'approved',
            'account_status' => $logisticsProfile->user->account_status,
        ]);
    }

    public function reject(Request $request, LogisticsProfile $logisticsProfile): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = LogisticsProfile::whereKey($logisticsProfile->id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($updated !== 1) {
            return response()->json(['success' => false, 'message' => 'This application has already been reviewed.'], 422);
        }
        $logisticsProfile->refresh();

        ActivityLogger::log('logistics.rejected', 'User Management', "Logistics registration rejected: {$logisticsProfile->business_name} — {$request->rejection_reason}", $logisticsProfile);

        $this->notifyDecision($logisticsProfile, false);

        return response()->json([
            'success' => true,
            'message' => "{$logisticsProfile->business_name} has been rejected.",
            'registration_status' => 'rejected',
            'account_status' => $logisticsProfile->user->account_status,
        ]);
    }

    public function updateAccountStatus(Request $request, LogisticsProfile $logisticsProfile)
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended,deactivated'],
        ]);

        if ($logisticsProfile->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved logistics partners can have their account status changed.',
            ], 422);
        }

        $logisticsProfile->user->update(['account_status' => $request->status]);

        ActivityLogger::log('logistics.account_status_changed', 'User Management', "{$logisticsProfile->business_name}'s account set to {$request->status}.", $logisticsProfile);

        return response()->json([
            'success' => true,
            'message' => "{$logisticsProfile->business_name}'s account is now {$request->status}.",
            'registration_status' => $logisticsProfile->status,
            'account_status' => $request->status,
        ]);
    }

    private function notifyDecision(LogisticsProfile $profile, bool $approved): void
    {
        try {
            $profile->user->notify(new LogisticsDecisionNotification($approved, $profile->rejection_reason));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
