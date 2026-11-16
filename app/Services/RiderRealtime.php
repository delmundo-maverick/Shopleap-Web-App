<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RiderRealtime
{
    public static function notify(?int $courierProfileId): void
    {
        self::notifyMany([$courierProfileId]);
    }

    /** @param iterable<int, int|null> $courierProfileIds CourierProfile primary keys, never user IDs. */
    public static function notifyMany(iterable $courierProfileIds): void
    {
        $url = config('services.supabase.url');
        $serviceKey = config('services.supabase.service_key');

        if (! is_string($url) || trim($url) === '' || ! is_string($serviceKey) || trim($serviceKey) === '') {
            return;
        }

        try {
            $messages = [];

            foreach ($courierProfileIds as $courierProfileId) {
                if ($courierProfileId === null || $courierProfileId <= 0) {
                    continue;
                }

                $messages[$courierProfileId] = [
                    'topic' => 'rider:'.$courierProfileId,
                    'event' => 'refresh',
                    'payload' => (object) [],
                ];
            }

            if ($messages === []) {
                return;
            }

            Http::asJson()
                ->withHeaders(['apikey' => $serviceKey])
                ->withToken($serviceKey)
                ->connectTimeout(2)
                ->timeout(3)
                ->post(rtrim(trim($url), '/').'/realtime/v1/api/broadcast', ['messages' => array_values($messages)]);
        } catch (Throwable) {
            return;
        }
    }
}
