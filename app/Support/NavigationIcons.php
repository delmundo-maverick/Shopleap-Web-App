<?php

namespace App\Support;

use Illuminate\Support\Str;

class NavigationIcons
{
    private const PATHS = [
        'dashboard' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'riders' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8M16 7l2 2 4-4',
        'areas' => 'M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1116 0zM15 10a3 3 0 11-6 0 3 3 0 016 0z',
        'pickup' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v10l9 5 9-5V8M12 13v10M8 4l9 5M7 16v-4m-2 2 2-2 2 2',
        'incoming' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v10l9 5 9-5V8M12 13v10M16 15h6m-3-3 3 3-3 3',
        'outbound' => 'M3 7h10v14H3zM6 3h4v4M16 12h6m-3-3 3 3-3 3M16 17h5v4h-5',
        'inbound' => 'M3 14h5l2 3h4l2-3h5v7H3v-7zM12 3v9m-4-4 4 4 4-4',
        'sorting' => 'M5 3v12a4 4 0 004 4h10M5 7h14M16 4l3 3-3 3M16 16l3 3-3 3',
        'history' => 'M3 11a9 9 0 119 10M3 4v7h7M12 7v5l3 2',
        'deliveries' => 'M3 6h11v11H3zM14 10h4l3 4v3h-7M7 21a2 2 0 100-4 2 2 0 000 4M17 21a2 2 0 100-4 2 2 0 000 4',
        'reports' => 'M4 3h11l5 5v13H4V3zM14 3v6h6M8 17v-3m4 3v-6m4 6v-4',
        'messages' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'support' => 'M9 9a3 3 0 016 0c0 2-3 2-3 4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'account' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'orders' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'returns' => 'M3 10h10a5 5 0 010 10H9M3 10l4-4M3 10l4 4',
    ];

    public static function path(string $concept): ?string
    {
        return self::PATHS[$concept] ?? null;
    }

    public static function forRoute(string $route): ?string
    {
        foreach ([
            '*.dashboard' => 'dashboard', '*.reports.*' => 'reports', 'messages.support' => 'support',
            'messages.*' => 'messages', '*.account.*' => 'account', '*.orders.*' => 'orders',
            '*.returns.*' => 'returns', 'logistics.riders.*' => 'riders', 'logistics.areas.*' => 'areas',
            'logistics.pickups.*' => 'pickup', 'logistics.incoming.*' => 'incoming',
            'logistics.outbound.*' => 'outbound', 'logistics.inbound.*' => 'inbound',
            'logistics.sorting.sessions.*' => 'history', 'logistics.sorting.*' => 'sorting',
            'logistics.deliveries.*' => 'deliveries', 'admin.settings.shipping.*' => 'deliveries',
        ] as $pattern => $concept) {
            if (Str::is($pattern, $route)) {
                return self::path($concept);
            }
        }

        return null;
    }
}
