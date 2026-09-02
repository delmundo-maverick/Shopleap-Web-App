<?php
// FILE PATH: app/Http/Controllers/Admin/SellerManagementController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SellerManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = SellerProfile::with('user')->latest();

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

        $sellers = $query->paginate(15)->withQueryString();

        return view('admin.registrations.sellers.index', compact('sellers'));
    }

    /**
     * AJAX — full profile details for the review modal.
     */
    public function details(SellerProfile $sellerProfile)
    {
        $sellerProfile->load('user');

        return response()->json([
            'id' => $sellerProfile->id,
            'full_name' => trim("{$sellerProfile->first_name} {$sellerProfile->middle_initial} {$sellerProfile->last_name}"),
            'sex' => $sellerProfile->sex,
            'email' => $sellerProfile->user->email,
            'contact_no' => $sellerProfile->contact_no,
            'birthday' => $sellerProfile->birthday->format('M d, Y'),
            'age' => $sellerProfile->age,
            'address' => "{$sellerProfile->street_address}, {$sellerProfile->barangay}, {$sellerProfile->municipality}, {$sellerProfile->province}",
            'business_name' => $sellerProfile->business_name,
            'line_of_business' => $sellerProfile->line_of_business,
            'id_url' => Storage::disk('public')->url($sellerProfile->id_upload_path),
            'permit_url' => Storage::disk('public')->url($sellerProfile->business_permit_path),
            'registration_status' => $sellerProfile->status,
            'rejection_reason' => $sellerProfile->rejection_reason,
            'account_status' => $sellerProfile->user->account_status,
            'submitted_at' => $sellerProfile->created_at->format('M d, Y \a\t g:i A'),
        ]);
    }

    public function approve(SellerProfile $sellerProfile)
    {
        $sellerProfile->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        // TODO: send approval email notification

        return response()->json([
            'success' => true,
            'message' => "{$sellerProfile->business_name} has been approved.",
            'registration_status' => 'approved',
            'account_status' => $sellerProfile->user->account_status,
        ]);
    }

    public function reject(Request $request, SellerProfile $sellerProfile)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $sellerProfile->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // TODO: send rejection email notification with reason

        return response()->json([
            'success' => true,
            'message' => "{$sellerProfile->business_name} has been rejected.",
            'registration_status' => 'rejected',
            'account_status' => $sellerProfile->user->account_status,
        ]);
    }

    /**
     * AJAX — activate / suspend / deactivate the underlying user account.
     * Only allowed once the registration itself has been approved.
     */
    public function updateAccountStatus(Request $request, SellerProfile $sellerProfile)
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended,deactivated'],
        ]);

        if ($sellerProfile->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved sellers can have their account status changed.',
            ], 422);
        }

        $sellerProfile->user->update(['account_status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "{$sellerProfile->business_name}'s account is now {$request->status}.",
            'registration_status' => $sellerProfile->status,
            'account_status' => $request->status,
        ]);
    }
}
