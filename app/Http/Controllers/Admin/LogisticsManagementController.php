<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogisticsManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticsProfile::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('business_name', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('email', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('reg_status') && $request->reg_status !== 'all') {
            $query->where('status', $request->reg_status);
        }

        if ($request->filled('acc_status') && $request->acc_status !== 'all') {
            $query->whereHas('user', fn($uq) => $uq->where('account_status', $request->acc_status));
        }

        $logisticsPartners = $query->paginate(15)->withQueryString();

        return view('admin.registrations.logistics.index', compact('logisticsPartners'));
    }

    public function details(LogisticsProfile $logisticsProfile)
    {
        $logisticsProfile->load('user');

        return response()->json([
            'id' => $logisticsProfile->id,
            'full_name' => trim("{$logisticsProfile->first_name} {$logisticsProfile->middle_initial} {$logisticsProfile->last_name}"),
            'sex' => $logisticsProfile->sex,
            'email' => $logisticsProfile->user->email,
            'contact_no' => $logisticsProfile->contact_no,
            'birthday' => $logisticsProfile->birthday->format('M d, Y'),
            'age' => $logisticsProfile->age,
            'address' => "{$logisticsProfile->street_address}, {$logisticsProfile->barangay}, {$logisticsProfile->municipality}, {$logisticsProfile->province}",
            'business_name' => $logisticsProfile->business_name,
            'id_url' => Storage::disk('r2')->temporaryUrl($logisticsProfile->id_upload_path, now()->addMinutes(10)),
            'permit_url' => Storage::disk('r2')->temporaryUrl($logisticsProfile->business_permit_path, now()->addMinutes(10)),
            'registration_status' => $logisticsProfile->status,
            'rejection_reason' => $logisticsProfile->rejection_reason,
            'account_status' => $logisticsProfile->user->account_status,
            'submitted_at' => $logisticsProfile->created_at->format('M d, Y \a\t g:i A'),
        ]);
    }

    public function approve(LogisticsProfile $logisticsProfile)
    {
        $logisticsProfile->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        // TODO: send approval email notification

        return response()->json([
            'success' => true,
            'message' => "{$logisticsProfile->business_name} has been approved.",
            'registration_status' => 'approved',
            'account_status' => $logisticsProfile->user->account_status,
        ]);
    }

    public function reject(Request $request, LogisticsProfile $logisticsProfile)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $logisticsProfile->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // TODO: send rejection email notification with reason

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

        return response()->json([
            'success' => true,
            'message' => "{$logisticsProfile->business_name}'s account is now {$request->status}.",
            'registration_status' => $logisticsProfile->status,
            'account_status' => $request->status,
        ]);
    }
}
