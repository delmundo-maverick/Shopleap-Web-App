<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $logisticsProfile = $request->user()->logisticsProfile;

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
            'pending' => CourierProfile::where('logistics_profile_id', $logisticsProfile->id)->where('status', 'pending')->count(),
            'approved' => CourierProfile::where('logistics_profile_id', $logisticsProfile->id)->where('status', 'approved')->count(),
            'rejected' => CourierProfile::where('logistics_profile_id', $logisticsProfile->id)->where('status', 'rejected')->count(),
        ];

        return view('logistics.riders.index', compact('riders', 'status', 'counts'));
    }

    public function approve(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        $rider->update([
            'status' => 'approved',
            'is_active' => true,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        ActivityLogger::log('rider.approved', 'Logistics', "{$rider->full_name} approved as a rider under {$rider->logisticsProfile->business_name}.", $rider);

        // TODO: notify rider by email once that system exists

        return back()->with('status', "{$rider->full_name} has been approved.");
    }

    public function reject(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $rider->update([
            'status' => 'rejected',
            'is_active' => false,
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        ActivityLogger::log('rider.rejected', 'Logistics', "{$rider->full_name}'s rider application rejected: {$request->rejection_reason}", $rider);

        return back()->with('status', "{$rider->full_name}'s application was rejected.");
    }

    public function toggleActive(Request $request, CourierProfile $rider)
    {
        $this->authorizeOwnership($request, $rider);

        if ($rider->status !== 'approved') {
            return back()->with('status', 'Only approved riders can be activated or deactivated.');
        }

        $rider->update(['is_active' => ! $rider->is_active]);

        return back()->with('status', "{$rider->full_name} is now " . ($rider->is_active ? 'active' : 'inactive') . '.');
    }

    private function authorizeOwnership(Request $request, CourierProfile $rider): void
    {
        if ($rider->logistics_profile_id !== $request->user()->logisticsProfile->id) {
            abort(403, 'You do not have access to this rider.');
        }
    }
}
