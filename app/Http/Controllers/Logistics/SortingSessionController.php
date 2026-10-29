<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\HubServiceArea;
use App\Models\Order;
use App\Models\SortingScan;
use App\Models\SortingSession;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SortingSessionController extends Controller
{
    public function start(Request $request)
    {
        $hub = $request->user()->logisticsProfile;
        $user = $request->user();

        // Check for an existing active session for this user or hub
        $existing = SortingSession::active()
            ->where(function ($q) use ($user, $hub) {
                $q->where('user_id', $user->id)
                    ->orWhere('logistics_profile_id', $hub->id);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'session' => $existing,
                'server_time' => now()->toIso8601String(),
                'message' => 'Resuming your active sorting session.',
            ]);
        }

        $session = SortingSession::create([
            'logistics_profile_id' => $hub->id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        ActivityLogger::log(
            'sorting_session_started',
            'sorting_session',
            "{$user->name} started a sorting session.",
            $session
        );

        return response()->json([
            'session' => $session,
            'server_time' => now()->toIso8601String(),
            'message' => 'Sorting session started.',
        ]);
    }

    public function scan(Request $request, SortingSession $session)
    {
        $hub = $request->user()->logisticsProfile;

        abort_unless($session->logistics_profile_id === $hub->id, 403);
        abort_unless($session->isActive(), 422, 'This sorting session has ended.');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($validated['code']);

        return DB::transaction(function () use ($session, $code, $hub) {
            // Parse the scanned code
            $orderId = $this->parseCode($code);

            if (! $orderId) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'result' => SortingScan::RESULT_REJECTED,
                    'message' => 'Unknown or invalid code format.',
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => 'Unknown or invalid code format.',
                    'scan' => $scan,
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Find the order
            $order = Order::with(['buyer.buyerProfile'])->find($orderId);

            if (! $order || $order->logistics_profile_id !== $hub->id) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order?->id,
                    'result' => SortingScan::RESULT_REJECTED,
                    'message' => $order ? 'This parcel belongs to a different hub.' : 'Order not found.',
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => $scan->message,
                    'scan' => $scan,
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Check for duplicate scan in this session
            $existingScan = $session->scans()
                ->where('order_id', $order->id)
                ->whereIn('result', [SortingScan::RESULT_SORTED, SortingScan::RESULT_NEEDS_REVIEW])
                ->lockForUpdate()
                ->first();

            if ($existingScan) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order->id,
                    'result' => SortingScan::RESULT_DUPLICATE,
                    'message' => "Order #{$order->id} was already scanned in this session.",
                    'destination_municipality' => $existingScan->destination_municipality,
                    'destination_province' => $existingScan->destination_province,
                    'destination_barangay' => $existingScan->destination_barangay,
                ]);

                return response()->json([
                    'result' => 'duplicate',
                    'message' => $scan->message,
                    'order_reference' => "#$order->id",
                    'scan' => $scan,
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Check order status eligibility
            if ($order->status !== Order::STATUS_AT_SORTING_CENTER) {
                $statusLabel = Order::STATUS_LABELS[$order->status] ?? $order->status;
                $reason = $this->getIneligibilityReason($order->status);

                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order->id,
                    'result' => SortingScan::RESULT_REJECTED,
                    'message' => "Order #{$order->id} is {$statusLabel}. {$reason}",
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => $scan->message,
                    'order_reference' => "#$order->id",
                    'scan' => $scan,
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Resolve destination
            $dest = $order->ship_to;
            $areas = $hub->serviceAreas()->with(['rider', 'barangays.rider'])->get()->keyBy(fn($a) => $a->area_key);
            $area = $areas->get(HubServiceArea::keyFor($dest['province'], $dest['municipality']));

            // If destination is not in service areas, mark as needs review
            if (! $area) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order->id,
                    'result' => SortingScan::RESULT_NEEDS_REVIEW,
                    'message' => "Order #{$order->id}: Destination not in your service areas.",
                    'destination_municipality' => $dest['municipality'],
                    'destination_province' => $dest['province'],
                    'destination_barangay' => $dest['barangay'],
                    'metadata' => ['reason' => 'no_service_area'],
                ]);

                return response()->json([
                    'result' => 'needs_review',
                    'message' => $scan->message,
                    'order_reference' => "#$order->id",
                    'destination' => $dest['municipality'] . ', ' . $dest['province'],
                    'scan' => $scan,
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Sort the order using existing logic
            $label = $area->zone_name ?: $dest['municipality'];
            $order->update(['status' => Order::STATUS_SORTED, 'delivery_area' => $label]);
            $order->logStatusEvent('Sorted for delivery to ' . $dest['municipality'] . '.');

            $scan = $session->scans()->create([
                'scanned_code' => $code,
                'order_id' => $order->id,
                'result' => SortingScan::RESULT_SORTED,
                'message' => "Order #{$order->id} sorted for {$label}.",
                'destination_municipality' => $dest['municipality'],
                'destination_province' => $dest['province'],
                'destination_barangay' => $dest['barangay'],
            ]);

            return response()->json([
                'result' => 'sorted',
                'message' => $scan->message,
                'order_reference' => "#$order->id",
                'destination' => $label,
                'group' => $area->zone_name ?: $dest['municipality'],
                'scan' => $scan,
                'progress' => $this->getProgress($session),
            ]);
        });
    }

    public function end(Request $request, SortingSession $session)
    {
        $hub = $request->user()->logisticsProfile;

        abort_unless($session->logistics_profile_id === $hub->id, 403);
        abort_unless($session->isActive(), 422, 'This session has already ended.');

        $endedAt = now();
        $duration = (int) floor($session->started_at->diffInSeconds($endedAt));

        $session->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
        ]);

        ActivityLogger::log(
            'sorting_session_ended',
            'sorting_session',
            "{$request->user()->name} ended a sorting session after " . gmdate('H:i:s', $duration) . '.',
            $session
        );

        return response()->json([
            'message' => 'Sorting session ended.',
            'summary' => $this->getSummary($session),
        ]);
    }

    public function summary(Request $request, SortingSession $session)
    {
        $hub = $request->user()->logisticsProfile;

        abort_unless($session->logistics_profile_id === $hub->id, 403);

        return response()->json($this->getSummary($session));
    }

    public function index(Request $request)
    {
        $hub = $request->user()->logisticsProfile;

        $sessions = SortingSession::forHub($hub->id)
            ->with('user')
            ->latest('started_at')
            ->paginate(20);

        return view('logistics.sorting.sessions.index', compact('sessions'));
    }

    private function parseCode(string $code): ?int
    {
        // QR code format: SHOPLEAP:ORDER:42
        if (preg_match('/^SHOPLEAP:ORDER:(\d+)$/i', $code, $matches)) {
            return (int) $matches[1];
        }

        // Barcode format: 0000000042 (10-digit zero-padded)
        if (preg_match('/^0*(\d+)$/', $code, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function getIneligibilityReason(string $status): string
    {
        return match ($status) {
            Order::STATUS_SORTED => 'Already sorted.',
            Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY => 'Already assigned to a rider.',
            Order::STATUS_DELIVERED, Order::STATUS_COMPLETED => 'Already delivered.',
            Order::STATUS_CANCELLED => 'This order was cancelled.',
            Order::STATUS_RETURNED => 'This order was returned.',
            Order::STATUS_DELIVERY_FAILED => 'Delivery failed.',
            default => 'Not ready for sorting.',
        };
    }

    private function getProgress(SortingSession $session): array
    {
        return [
            'total_scans' => $session->scans()->count(),
            'sorted' => $session->sortedCount(),
            'needs_review' => $session->needsReviewCount(),
            'rejected' => $session->rejectedCount(),
            'duplicates' => $session->duplicateCount(),
        ];
    }

    private function getSummary(SortingSession $session): array
    {
        $session->load(['scans.order', 'user']);

        return [
            'session_id' => $session->id,
            'user_name' => $session->user->name,
            'started_at' => $session->started_at->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'duration_seconds' => $session->duration_seconds ?? $session->elapsedSeconds(),
            'duration_formatted' => gmdate('H:i:s', $session->duration_seconds ?? $session->elapsedSeconds()),
            'auto_closed' => $session->auto_closed,
            'total_scans' => $session->scans->count(),
            'sorted' => $session->sortedCount(),
            'needs_review' => $session->needsReviewCount(),
            'rejected' => $session->rejectedCount(),
            'duplicates' => $session->duplicateCount(),
            'assigned_during_session' => $session->assignedDuringSessionCount(),
        ];
    }
}
