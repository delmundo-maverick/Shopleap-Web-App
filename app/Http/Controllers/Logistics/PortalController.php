<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function application(Request $request): View|RedirectResponse
    {
        $profile = $this->profile($request);
        if ($profile->status === 'approved') {
            return redirect()->route('logistics.dashboard');
        }

        return view('logistics.application', compact('profile'));
    }

    public function resubmit(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        abort_unless($profile->status === 'rejected', 403);
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'company_name' => ['required', 'string', 'max:150'],
            'service_regions' => ['required', 'array', 'min:1'],
            'service_regions.*' => ['required', 'string', 'distinct', Rule::in(config('logistics.regions'))],
            'id_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'business_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'sec_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bir_registration' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $documents = [];
        foreach (['id_upload', 'business_permit', 'sec_certificate', 'bir_registration'] as $field) {
            $documents[$field.'_path'] = $request->file($field)->store('logistics_uploads/applications', 'r2');
        }

        $profile->update([
            ...$documents,
            'business_name' => $validated['business_name'],
            'company_name' => preg_replace('/\s+/', ' ', trim($validated['company_name'])),
            'service_regions' => $validated['service_regions'],
            'status' => 'pending',
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->route('logistics.application')->with('status', 'Your updated application has been submitted for review.');
    }

    private function profile(Request $request): LogisticsProfile
    {
        abort_unless($request->user()?->role === 'logistics' && $request->user()->account_status === 'active', 403);

        return $request->user()->logisticsProfile ?? abort(403);
    }
}
