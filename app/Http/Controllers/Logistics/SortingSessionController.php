<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\HubServiceArea;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\SortingScan;
use App\Models\SortingSession;
use App\Services\ActivityLogger;
use App\Services\HubContext;
use App\Services\HubCoverage;
use App\Services\ParcelLifecycle;
use App\Support\ScanCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SortingSessionController extends Controller
{
    public function start(Request $request)
    {
        $hub = HubContext::require($request);
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

    public function scan(Request $request, SortingSession $session, HubCoverage $coverage, ParcelLifecycle $lifecycle)
    {
        $hub = HubContext::require($request);

        abort_unless($session->logistics_profile_id === $hub->id, 403);
        abort_unless($session->isActive(), 422, 'This sorting session has ended.');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($validated['code']);

        return DB::transaction(function () use ($session, $code, $hub, $coverage, $request, $lifecycle) {
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
                    'scan' => $this->publicScan($scan, $order ?? null),
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Find the order
            $order = Order::with(['buyer.buyerProfile'])->find($orderId);

            if (! $order || Gate::denies('actAsHoldingHub', $order)) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order?->id,
                    'result' => SortingScan::RESULT_REJECTED,
                    'message' => $order ? 'This parcel belongs to a different hub.' : 'Order not found.',
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => $scan->message,
                    'scan' => $this->publicScan($scan, $order ?? null),
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
                    'message' => "Order #{$order->order_number} was already scanned in this session.",
                    'destination_municipality' => $existingScan->destination_municipality,
                    'destination_province' => $existingScan->destination_province,
                    'destination_barangay' => $existingScan->destination_barangay,
                    'metadata' => $existingScan->metadata,
                ]);

                return response()->json([
                    'result' => 'duplicate',
                    'message' => $scan->message,
                    'order_reference' => "#$order->order_number",
                    'scan' => $this->publicScan($scan, $order ?? null),
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
                    'message' => "Order #{$order->order_number} is {$statusLabel}. {$reason}",
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => $scan->message,
                    'order_reference' => "#$order->order_number",
                    'scan' => $this->publicScan($scan, $order ?? null),
                    'progress' => $this->getProgress($session),
                ]);
            }

            // Resolve the destination hub from the buyer's barangay.
            $dest = $order->ship_to;
            $destination = $coverage->destinationHubFor(
                $hub,
                $dest['province'],
                $dest['municipality'],
                $dest['barangay']
            );

            // If no hub in this company serves the barangay, mark as needs review.
            if (! $destination) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order->id,
                    'result' => SortingScan::RESULT_NEEDS_REVIEW,
                    'message' => "Order #{$order->order_number}: No destination hub in your company serves Brgy. {$dest['barangay']}, {$dest['municipality']}.",
                    'destination_municipality' => $dest['municipality'],
                    'destination_province' => $dest['province'],
                    'destination_barangay' => $dest['barangay'],
                    'metadata' => ['reason' => 'no_destination_hub'],
                ]);

                return response()->json([
                    'result' => 'needs_review',
                    'message' => $scan->message,
                    'order_reference' => "#$order->order_number",
                    'destination' => $dest['municipality'].', '.$dest['province'],
                    'scan' => $this->publicScan($scan, $order ?? null),
                    'progress' => $this->getProgress($session),
                ]);
            }

            $label = $coverage->deliveryAreaLabel($destination, $dest['province'], $dest['municipality']);
            $sameHub = (int) $destination->id === (int) $hub->id;

            $note = $sameHub
                ? 'Sorted for delivery to '.$dest['municipality'].'.'
                : 'Sorted and awaiting transfer to '.$destination->business_name.'.';

            $moved = $lifecycle->transition(
                $order,
                Order::STATUS_AT_SORTING_CENTER,
                $sameHub ? Order::STATUS_SORTED : Order::STATUS_AWAITING_TRANSFER,
                [
                    'origin_hub_id' => $order->origin_hub_id ?? $hub->id,
                    'destination_hub_id' => $destination->id,
                    'current_hub_id' => $hub->id,
                    'delivery_area' => $label,
                ],
                $note,
                $request->user()->id,
                $hub->id,
                eventType: ParcelEvent::TYPE_SORTED,
            );

            if (! $moved) {
                $scan = $session->scans()->create([
                    'scanned_code' => $code,
                    'order_id' => $order->id,
                    'result' => SortingScan::RESULT_REJECTED,
                    'message' => "Order #{$order->order_number} was just updated. Please scan it again.",
                ]);

                return response()->json([
                    'result' => 'rejected',
                    'message' => $scan->message,
                    'order_reference' => "#$order->order_number",
                    'scan' => $this->publicScan($scan, $order ?? null),
                    'progress' => $this->getProgress($session),
                ]);
            }

            $scan = $session->scans()->create([
                'scanned_code' => $code,
                'order_id' => $order->id,
                'result' => SortingScan::RESULT_SORTED,
                'message' => $sameHub
                    ? "Order #{$order->order_number} sorted for {$label}."
                    : "Order #{$order->order_number} sorted and awaiting transfer to {$destination->business_name}.",
                'destination_municipality' => $dest['municipality'],
                'destination_province' => $dest['province'],
                'destination_barangay' => $dest['barangay'],
                'metadata' => [
                    'destination_hub_id' => $destination->id,
                    'destination_hub_name' => $destination->business_name,
                    'awaiting_transfer' => ! $sameHub,
                ],
            ]);

            return response()->json([
                'result' => 'sorted',
                'message' => $scan->message,
                'order_reference' => "#$order->order_number",
                'destination' => $label,
                'destination_hub' => $destination->business_name,
                'group' => $destination->business_name.' · '.($dest['barangay'] ?: $dest['municipality']),
                'scan' => $this->publicScan($scan, $order ?? null),
                'progress' => $this->getProgress($session),
            ]);
        });
    }

    public function end(Request $request, SortingSession $session)
    {
        $hub = HubContext::require($request);

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
            "{$request->user()->name} ended a sorting session after ".gmdate('H:i:s', $duration).'.',
            $session
        );

        return response()->json([
            'message' => 'Sorting session ended.',
            'summary' => $this->getSummary($session),
        ]);
    }

    public function summary(Request $request, SortingSession $session)
    {
        $hub = HubContext::require($request);

        abort_unless($session->logistics_profile_id === $hub->id, 403);

        return response()->json($this->getSummary($session));
    }

    public function index(Request $request)
    {
        $hub = HubContext::require($request);

        $sessions = SortingSession::forHub($hub->id)
            ->with('user')
            ->latest('started_at')
            ->paginate(20);

        return view('logistics.sorting.sessions.index', compact('sessions'));
    }

    /** @return array<string, mixed> */
    private function publicScan(SortingScan $scan, ?Order $order): array
    {
        $data = $scan->toArray();
        $data['order_id'] = $order?->order_number;

        return $data;
    }

    private function parseCode(string $code): ?int
    {
        return ScanCode::orderId($code);
    }

    private function getIneligibilityReason(string $status): string
    {
        return match ($status) {
            Order::STATUS_SORTED => 'Already sorted.',
            Order::STATUS_AWAITING_TRANSFER => 'Already sorted and awaiting transfer.',
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
        $session->load(['scans.order.destinationHub', 'user']);

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
            'awaiting_transfer' => $session->scans
                ->where('result', SortingScan::RESULT_SORTED)
                ->filter(fn (SortingScan $scan) => (bool) ($scan->metadata['awaiting_transfer'] ?? false))
                ->count(),
            'needs_review' => $session->needsReviewCount(),
            'rejected' => $session->rejectedCount(),
            'duplicates' => $session->duplicateCount(),
            'assigned_during_session' => $session->assignedDuringSessionCount(),
            'groups' => $this->summaryGroups($session),
        ];
    }

    /**
     * Sorted scans grouped by destination hub first, then by barangay.
     *
     * @return array<int, array{
     *     hub_id: int|null,
     *     hub_name: string,
     *     company: string|null,
     *     total: int,
     *     barangays: array<int, array{barangay: string, count: int, order_ids: array<int, string>}>
     * }>
     */
    private function summaryGroups(SortingSession $session): array
    {
        return $session->scans
            ->where('result', SortingScan::RESULT_SORTED)
            ->filter(fn (SortingScan $scan) => $scan->order !== null)
            ->groupBy(fn (SortingScan $scan) => $scan->order->destination_hub_id ?? 'unknown')
            ->map(function ($scans, $hubId) {
                $first = $scans->first();
                $hub = $first->order->destinationHub;

                $barangays = $scans
                    ->groupBy(fn (SortingScan $scan) => HubServiceArea::barangayKey(
                        $scan->destination_barangay ?: $scan->order->ship_to['barangay']
                    ))
                    ->map(function ($group) {
                        $scan = $group->first();

                        return [
                            'barangay' => $scan->destination_barangay
                                ?: $scan->order->ship_to['barangay']
                                ?: 'Unknown barangay',
                            'count' => $group->count(),
                            'order_ids' => $group->pluck('order.order_number')->filter()->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'hub_id' => is_numeric($hubId) ? (int) $hubId : null,
                    'hub_name' => $hub?->business_name ?? 'Unknown hub',
                    'company' => $hub?->company_name,
                    'total' => $scans->count(),
                    'barangays' => $barangays,
                ];
            })
            ->values()
            ->all();
    }
}
