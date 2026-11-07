<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Notifications\RegistrationDecisionNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuyerManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = BuyerProfile::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('reg_status') && $request->reg_status !== 'all') {
            $query->where('status', $request->reg_status);
        }

        if ($request->filled('acc_status') && $request->acc_status !== 'all') {
            $query->whereHas('user', fn ($uq) => $uq->where('account_status', $request->acc_status));
        }

        $buyers = $query->paginate(15)->withQueryString();

        return view('admin.registrations.buyers.index', compact('buyers'));
    }

    public function details(BuyerProfile $buyerProfile)
    {
        $buyerProfile->load('user');

        return response()->json([
            'id' => $buyerProfile->id,
            'full_name' => trim("{$buyerProfile->first_name} {$buyerProfile->middle_initial} {$buyerProfile->last_name}"),
            'sex' => $buyerProfile->sex,
            'email' => $buyerProfile->user->email,
            'contact_no' => $buyerProfile->contact_no,
            'birthday' => $buyerProfile->birthday->format('M d, Y'),
            'age' => $buyerProfile->age,
            'address' => trim("{$buyerProfile->house_number} {$buyerProfile->street}, {$buyerProfile->barangay}, {$buyerProfile->municipality}, {$buyerProfile->province}")
                .($buyerProfile->additional_address ? " ({$buyerProfile->additional_address})" : ''),
            'id_url' => Storage::disk('r2')->temporaryUrl($buyerProfile->id_upload_path, now()->addMinutes(10)),
            'registration_status' => $buyerProfile->status,
            'rejection_reason' => $buyerProfile->rejection_reason,
            'account_status' => $buyerProfile->user->account_status,
            'submitted_at' => $buyerProfile->created_at->format('M d, Y \a\t g:i A'),
        ]);
    }

    public function approve(BuyerProfile $buyerProfile): JsonResponse
    {
        $updated = BuyerProfile::whereKey($buyerProfile->id)->where('status', 'pending')->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        if ($updated !== 1) {
            return response()->json(['success' => false, 'message' => 'This application has already been reviewed.'], 422);
        }
        $buyerProfile->refresh();

        ActivityLogger::log('buyer.approved', 'User Management', "Buyer registration approved: {$buyerProfile->first_name} {$buyerProfile->last_name}.", $buyerProfile);

        $this->notifyDecision($buyerProfile, true);

        return response()->json([
            'success' => true,
            'message' => "{$buyerProfile->first_name} {$buyerProfile->last_name} has been approved.",
            'registration_status' => 'approved',
            'account_status' => $buyerProfile->user->account_status,
        ]);
    }

    public function reject(Request $request, BuyerProfile $buyerProfile): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = BuyerProfile::whereKey($buyerProfile->id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($updated !== 1) {
            return response()->json(['success' => false, 'message' => 'This application has already been reviewed.'], 422);
        }
        $buyerProfile->refresh();

        ActivityLogger::log('buyer.rejected', 'User Management', "Buyer registration rejected: {$buyerProfile->first_name} {$buyerProfile->last_name} — {$request->rejection_reason}", $buyerProfile);

        $this->notifyDecision($buyerProfile, false);

        return response()->json([
            'success' => true,
            'message' => "{$buyerProfile->first_name} {$buyerProfile->last_name} has been rejected.",
            'registration_status' => 'rejected',
            'account_status' => $buyerProfile->user->account_status,
        ]);
    }

    public function updateAccountStatus(Request $request, BuyerProfile $buyerProfile)
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended,deactivated'],
        ]);

        if ($buyerProfile->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved buyers can have their account status changed.',
            ], 422);
        }

        $buyerProfile->user->update(['account_status' => $request->status]);

        ActivityLogger::log('buyer.account_status_changed', 'User Management', "{$buyerProfile->first_name} {$buyerProfile->last_name}'s account set to {$request->status}.", $buyerProfile);

        return response()->json([
            'success' => true,
            'message' => "{$buyerProfile->first_name} {$buyerProfile->last_name}'s account is now {$request->status}.",
            'registration_status' => $buyerProfile->status,
            'account_status' => $request->status,
        ]);
    }

    private function notifyDecision(BuyerProfile $profile, bool $approved): void
    {
        try {
            $profile->user->notify(new RegistrationDecisionNotification('buyer', $approved, $profile->rejection_reason));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
