<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Notifications\RiderDecisionNotification;
use App\Services\ActivityLogger;
use App\Services\HubContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $logisticsProfile = HubContext::require($request);

        $query = CourierProfile::with('user')
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('plate_number', 'ilike', "%{$search}%");
            });
        }

        $status = $request->get('status', 'pending');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $riders = $query->paginate(15)->withQueryString();

        $counts = [
            'pending' => CourierProfile::where(
                'logistics_profile_id',
                $logisticsProfile->id
            )->where('status', 'pending')->count(),

            'approved' => CourierProfile::where(
                'logistics_profile_id',
                $logisticsProfile->id
            )->where('status', 'approved')->count(),

            'rejected' => CourierProfile::where(
                'logistics_profile_id',
                $logisticsProfile->id
            )->where('status', 'rejected')->count(),
        ];

        return view(
            'logistics.riders.index',
            compact('riders', 'status', 'counts')
        );
    }

    public function approve(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        // Approve only if the rider is still pending.
        $moved = CourierProfile::whereKey($rider->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'is_active' => true,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]) > 0;

        if (! $moved) {
            return back()->with(
                'status',
                "{$rider->full_name} was already reviewed."
            );
        }

        $rider->refresh();

        // The rider's registration barangay becomes theirs automatically.
        $mapping = $rider->mapHomeBarangay();

        ActivityLogger::log(
            'rider.approved',
            'Logistics',
            "{$rider->full_name} approved as a rider under {$rider->logisticsProfile->business_name}. {$mapping['message']}",
            $rider
        );

        $this->notifyRider($rider, true);

        return back()->with(
            'status',
            "{$rider->full_name} has been approved. {$mapping['message']}"
        );
    }

    public function reject(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        // Reject only if the rider is still pending.
        $moved = CourierProfile::whereKey($rider->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'is_active' => false,
                'reviewed_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]) > 0;

        if (! $moved) {
            return back()->with(
                'status',
                "{$rider->full_name} was already reviewed. Use Deactivate for approved riders."
            );
        }

        $rider->refresh();

        ActivityLogger::log(
            'rider.rejected',
            'Logistics',
            "{$rider->full_name}'s rider application rejected: {$request->rejection_reason}",
            $rider
        );

        $this->notifyRider(
            $rider,
            false,
            $request->rejection_reason
        );

        return back()->with(
            'status',
            "{$rider->full_name}'s application was rejected."
        );
    }

    /**
     * Send a decision email without undoing or blocking the decision
     * if email delivery fails.
     */
    private function notifyRider(
        CourierProfile $rider,
        bool $approved,
        ?string $reason = null
    ): void {
        try {
            Notification::route('mail', $rider->user->email)
                ->notify(new RiderDecisionNotification(
                    $approved,
                    $rider->first_name,
                    $rider->logisticsProfile->business_name,
                    $reason
                ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function toggleActive(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        if ($rider->status !== 'approved') {
            return back()->with(
                'status',
                'Only approved riders can be activated or deactivated.'
            );
        }

        $rider->update([
            'is_active' => ! $rider->is_active,
        ]);

        return back()->with(
            'status',
            "{$rider->full_name} is now ".
                ($rider->is_active ? 'active' : 'inactive').
                '.'
        );
    }

    private function authorizeOwnership(
        Request $request,
        CourierProfile $rider
    ): void {
        if (
            $rider->logistics_profile_id !==
            $request->user()->logisticsProfile->id
        ) {
            abort(403, 'You do not have access to this rider.');
        }
    }
}
