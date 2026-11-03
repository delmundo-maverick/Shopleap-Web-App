<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ParcelEvent;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The single place where a parcel changes status or records a tracking event.
 *
 * Controllers, commands and the courier API call this service so every move is
 * a conditional update (double taps cannot apply twice), writes the public
 * order timeline and writes the parcel_events row that powers tracking.
 */
class ParcelLifecycle
{
    /**
     * Move an order from one status (or any of several) to another.
     *
     * @param  string|array<int, string>  $from
     * @param  array<string, mixed>  $values  Extra columns written with the status.
     * @param  string|Closure(Order): string|null  $note
     * @param  Closure(Builder): void|null  $where
     * @param  Closure(Order): void|null  $after
     */
    public function transition(
        Order $order,
        string|array $from,
        string $to,
        array $values = [],
        string|Closure|null $note = null,
        ?int $actorId = null,
        ?int $hubId = null,
        ?int $riderId = null,
        ?string $eventType = null,
        ?Closure $where = null,
        ?Closure $after = null,
    ): bool {
        return DB::transaction(function () use (
            $order,
            $from,
            $to,
            $values,
            $note,
            $actorId,
            $hubId,
            $riderId,
            $eventType,
            $where,
            $after
        ) {
            $query = Order::whereKey($order->id);

            if (is_array($from)) {
                $query->whereIn('status', $from);
            } else {
                $query->where('status', $from);
            }

            if ($where) {
                $where($query);
            }

            if ($query->update(['status' => $to] + $values) === 0) {
                return false;
            }

            $order->refresh();

            $resolvedNote = $note instanceof Closure ? $note($order) : $note;

            $order->logStatusEvent($resolvedNote, $actorId);

            $this->record($order, $eventType ?? ParcelEvent::TYPE_STATUS_CHANGED, [
                'hub_id' => $hubId,
                'rider_id' => $riderId,
                'from_status' => is_array($from) ? null : $from,
                'to_status' => $to,
                'note' => $resolvedNote,
                'actor_user_id' => $actorId,
            ]);

            if ($after) {
                $after($order);
            }

            return true;
        });
    }

    /**
     * Record the order's initial status when the buyer places it.
     * Placement is not a transition, but it belongs in the tracking timeline.
     */
    public function placed(Order $order, ?int $actorId = null): void
    {
        $order->logStatusEvent(null, $actorId);

        $this->record($order, ParcelEvent::TYPE_STATUS_CHANGED, [
            'to_status' => $order->status,
            'actor_user_id' => $actorId,
        ]);
    }

    /**
     * Record a tracking event without changing the order status.
     *
     * @param  array{hub_id?: int|null, rider_id?: int|null, from_status?: string|null, to_status?: string|null, note?: string|null, actor_user_id?: int|null}  $attributes
     */
    public function record(Order $order, string $eventType, array $attributes = []): ParcelEvent
    {
        $hubId = array_key_exists('hub_id', $attributes)
            ? $attributes['hub_id']
            : ($order->current_hub_id ?? $order->logistics_profile_id);

        $actorId = array_key_exists('actor_user_id', $attributes)
            ? $attributes['actor_user_id']
            : Auth::id();

        return $order->parcelEvents()->create([
            'hub_id' => $hubId,
            'rider_id' => $attributes['rider_id'] ?? null,
            'event_type' => $eventType,
            'from_status' => $attributes['from_status'] ?? null,
            'to_status' => $attributes['to_status'] ?? null,
            'note' => $attributes['note'] ?? null,
            'actor_user_id' => $actorId,
        ]);
    }
}
