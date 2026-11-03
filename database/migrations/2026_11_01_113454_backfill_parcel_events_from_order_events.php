<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Copy the existing public order events into parcel_events so tracking
     * timelines keep their history after the first new parcel event arrives.
     * Orders that already have parcel events are left untouched.
     */
    public function up(): void
    {
        $orderIds = DB::table('orders')
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('order_events')
                ->whereColumn('order_events.order_id', 'orders.id')
                ->whereNotNull('order_events.status'))
            ->whereNotExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('parcel_events')
                ->whereColumn('parcel_events.order_id', 'orders.id'))
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return;
        }

        $orders = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->get(['id', 'current_hub_id', 'logistics_profile_id'])
            ->keyBy('id');

        DB::table('order_events')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('status')
            ->orderBy('id')
            ->chunkById(500, function ($events) use ($orders) {
                $rows = [];

                foreach ($events as $event) {
                    $order = $orders->get($event->order_id);

                    $rows[] = [
                        'order_id' => $event->order_id,
                        'hub_id' => $order->current_hub_id ?? $order->logistics_profile_id,
                        'rider_id' => null,
                        'event_type' => 'status_changed',
                        'from_status' => null,
                        'to_status' => $event->status,
                        'note' => $event->note,
                        'actor_user_id' => $event->actor_id,
                        'created_at' => $event->created_at,
                        'updated_at' => $event->created_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('parcel_events')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only; the original order events remain intact.
    }
};
