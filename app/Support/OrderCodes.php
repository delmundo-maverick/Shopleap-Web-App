<?php

namespace App\Support;

use App\Models\Order;

class OrderCodes
{
    public static function generate(string $prefix, int $length): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $code = '';
        for ($index = 0; $index < $length; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $prefix.'-'.$code;
    }

    public static function presentNotifications(iterable $notifications): void
    {
        $notifications = collect($notifications);
        $ids = $notifications->map(fn ($notification) => $notification->data['meta']['order_id'] ?? null)
            ->filter(fn ($id): bool => is_int($id) || (is_string($id) && ctype_digit($id)))->unique();
        if ($ids->isEmpty()) {
            return;
        }
        $orders = Order::whereIn('id', $ids)->get()->keyBy('id');
        foreach ($notifications as $notification) {
            $data = $notification->data;
            $id = $data['meta']['order_id'] ?? null;
            $order = $orders->get($id);
            if (! $order) {
                continue;
            }
            $data['meta']['order_id'] = $order->order_number;
            $data['meta']['order_number'] = $order->order_number;
            $data['meta']['waybill_code'] = $order->waybill_code;
            foreach (['title', 'body'] as $field) {
                if (isset($data[$field])) {
                    $data[$field] = preg_replace_callback('/(order\s*#?)'.preg_quote((string) $id, '/').'\b/i',
                        fn (array $matches): string => $matches[1].$order->order_number, $data[$field]);
                }
            }
            if (is_string($data['url'] ?? null)) {
                $data['url'] = preg_replace('~(/orders/)'.preg_quote((string) $id, '~').'(?=/|$|\?)~', '${1}'.$order->order_number, $data['url']);
            }
            $notification->setAttribute('data', $data);
        }
    }
}
