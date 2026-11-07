<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\RiderApplicationsRequest;
use App\Models\CourierProfile;
use App\Notifications\RiderDecisionNotification;
use App\Services\ActivityLogger;
use App\Services\HubContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiderController extends Controller
{
    public function index(RiderApplicationsRequest $request): View
    {
        $hubId = HubContext::require($request)->id;
        $filters = $request->validated();
        $status = $filters['status'] ?? 'pending';
        $search = $filters['search'] ?? '';
        $query = CourierProfile::with('user')->where('logistics_profile_id', $hubId)->latest();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->whereLike('first_name', "%{$search}%")
                    ->orWhereLike('last_name', "%{$search}%")
                    ->orWhereLike('plate_number', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereLike('email', "%{$search}%"));
            });
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $riders = $query->paginate(15)->withQueryString();
        $counts = CourierProfile::where('logistics_profile_id', $hubId)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $counts = array_replace(['pending' => 0, 'approved' => 0, 'rejected' => 0], $counts);

        return view('logistics.riders.index', compact('riders', 'status', 'search', 'counts'));
    }

    public function show(Request $request, CourierProfile $rider): Response
    {
        $this->authorizeOwnership($request, $rider);
        $rider->load('user');

        return response()->view('logistics.riders.show', compact('rider'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function document(Request $request, CourierProfile $rider, string $document): StreamedResponse
    {
        $this->authorizeOwnership($request, $rider);
        $path = match ($document) {
            'license' => $rider->license_upload_path,
            'or-cr' => $rider->or_cr_upload_path,
            default => abort(404),
        };
        $disk = Storage::disk('r2');
        abort_unless($path && $disk->exists($path), 404);
        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'",
        ]);
    }

    public function approve(Request $request, CourierProfile $rider): RedirectResponse
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
            return redirect()->route('logistics.riders.show', $rider)->with(
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

        return redirect()->route('logistics.riders.show', $rider)->with(
            'status',
            "{$rider->full_name} has been approved. {$mapping['message']}"
        );
    }

    public function reject(Request $request, CourierProfile $rider): RedirectResponse
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
            return redirect()->route('logistics.riders.show', $rider)->with(
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

        return redirect()->route('logistics.riders.show', $rider)->with(
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

    public function toggleActive(Request $request, CourierProfile $rider): RedirectResponse
    {
        $this->authorizeOwnership($request, $rider);

        if ($rider->status !== 'approved') {
            return redirect()->route('logistics.riders.show', $rider)->with(
                'status',
                'Only approved riders can be activated or deactivated.'
            );
        }

        $rider->update([
            'is_active' => ! $rider->is_active,
        ]);

        return redirect()->route('logistics.riders.show', $rider)->with(
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
            HubContext::require($request)->id
        ) {
            abort(403, 'You do not have access to this rider.');
        }
    }
}
