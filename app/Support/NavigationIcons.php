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
        'rider-rate' => 'M12 3v18M17 7H9a4 4 0 000 8h6a4 4 0 000-8M7 17h10',
        'reports' => 'M4 3h11l5 5v13H4V3zM14 3v6h6M8 17v-3m4 3v-6m4 6v-4',
        'messages' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'support' => 'M9 9a3 3 0 016 0c0 2-3 2-3 4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'account' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'orders' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'returns' => 'M3 10h10a5 5 0 010 10H9M3 10l4-4M3 10l4 4',
        'home' => 'M3 10l9-7 9 7M5 9v12h5v-7h4v7h5V9',
        'products' => 'M12 3l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 16l9 5 9-5',
        'add-product' => 'M3 5h12v16H3zM3 9h12M7 5v4M19 3v8m-4-4h8',
        'prepare' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v10l9 5 9-5V8M12 13v10M8 5l9 5M8 16l2 2 4-4',
        'earnings' => 'M3 20h18M5 16l5-5 4 3 7-9M16 5h5v5',
        'ledger' => 'M4 3h14a2 2 0 012 2v16H6a2 2 0 010-4h14M4 3v16M8 7h8M8 11h6',
        'payouts' => 'M3 7h18v14H3zM3 11h18M7 16h3M8 3h8M12 3v4',
        'settlements' => 'M3 5h18v14H3zM3 9h18M7 14h4M14 15l2 2 4-4',
        'oversight' => 'M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3zM8 12l3 3 5-6',
        'cash' => 'M3 6h18v12H3zM15 12a3 3 0 11-6 0 3 3 0 016 0zM6 9h.01M18 15h.01M6 21h12',
        'remittances' => 'M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4',
        'refunds' => 'M3 10h12a5 5 0 010 10h-3M3 10l4-4m-4 4 4 4M11 3h10v5H11zM16 5.5h.01',
        'settings' => 'M4 6h16M4 12h16M4 18h16M9 3v6m6 0v6m-3 0v6',
        'remittance-settings' => 'M3 8l9-5 9 5H3zM5 10v7m5-7v7m4-7v7m5-7v7M3 21h18M3 18h18',
        'transactions' => 'M4 3h16v18l-4-2-4 2-4-2-4 2V3zM8 7h8M8 11h8M8 15h4',
        'buyers' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M13 7a4 4 0 11-8 0 4 4 0 018 0zM17 4a4 4 0 010 8M22 21v-2a4 4 0 00-3-3.87',
        'store' => 'M3 9l2-6h14l2 6M3 9a3 3 0 006 0 3 3 0 006 0 3 3 0 006 0M5 12v9h14v-9M9 21v-6h6v6',
        'hubs' => 'M3 10l9-7 9 7v11H3V10zM7 21V11h10v10M7 15h10M7 18h10',
        'logs' => 'M5 3h14v18H5zM8 7h8M8 12h2l2-3 2 6 2-3',
        'shipping' => 'M3 5h18v5H3zM5 10v11h14V10M9 15h6M9 18h6M9 5v5m6-5v5',
        'announcements' => 'M3 10v4h4l12 5V5L7 10H3zM7 14l2 7h4l-2-5M22 9v6',
        'policies' => 'M5 3h10l4 4v14H5V3zM14 3v5h5M8 12h8M8 16h6',
        'discounts' => 'M4 3h7l10 10-8 8L3 11V4l1-1zM7 7h.01M10 16l6-6M11 11h.01M15 15h.01',
        'vouchers' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
        'reviews' => 'M12 3l2.78 5.63L21 9.54l-4.5 4.39 1.06 6.2L12 17.2l-5.56 2.93 1.06-6.2L3 9.54l6.22-.91L12 3z',
        'wishlist' => 'M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 00-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 000-7.8z',
        'notifications' => 'M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
        'tracking' => 'M3 4h11v13H3zM14 8h4l3 4v5h-7M7 21a2 2 0 100-4 2 2 0 000 4M17 21a2 2 0 100-4 2 2 0 000 4M6 8h5m-5 4h3',
    ];

    public static function path(string $concept): ?string
    {
        return self::PATHS[$concept] ?? null;
    }

    /** @param array<string, mixed> $parameters */
    public static function forRoute(string $route, array $parameters = []): ?string
    {
        if (Str::is('*.movements.*', $route)) {
            return self::path(match ($parameters['section'] ?? 'remittances') {
                'cash' => 'cash',
                'settings' => 'remittance-settings',
                default => 'remittances',
            });
        }

        if ($route === 'seller.orders.index' && ($parameters['status'] ?? null) === 'prepare') {
            return self::path('prepare');
        }

        foreach ([
            'logistics.rider-rate.*' => 'rider-rate',
            'seller.earnings.*' => 'earnings', 'admin.ledger.*' => 'ledger', 'logistics.money.*' => 'cash',
            '*.payouts.*' => 'payouts', 'admin.refunds.*' => 'refunds',
            'admin.settlements.*' => 'oversight', '*.settlements.*' => 'settlements',
            'admin.registrations.buyers.*' => 'buyers', 'admin.registrations.sellers.*' => 'store',
            'admin.registrations.logistics.*' => 'hubs', 'admin.transactions.*' => 'transactions',
            'admin.logs.*' => 'logs', 'admin.settings.shipping.*' => 'shipping',
            'admin.settings.announcements.*' => 'announcements', 'admin.settings.policies.*' => 'policies',
            'admin.settings.*' => 'settings', 'seller.products.discounts.*' => 'discounts',
            'seller.products.create' => 'add-product', '*.products.*' => 'products',
            'seller.reviews.*' => 'reviews', '*.vouchers.*' => 'vouchers',
            'buyer.home' => 'home', 'buyer.wishlist.*' => 'wishlist', 'notifications.*' => 'notifications',
            '*.dashboard' => 'dashboard', '*.reports.*' => 'reports', 'messages.support' => 'support',
            'messages.*' => 'messages', '*.account.*' => 'account', '*.orders.*' => 'orders',
            '*.returns.*' => 'returns', 'logistics.riders.*' => 'riders', 'logistics.areas.*' => 'areas',
            'logistics.pickups.*' => 'pickup', 'logistics.incoming.*' => 'incoming',
            'logistics.outbound.*' => 'outbound', 'logistics.inbound.*' => 'inbound',
            'logistics.sorting.sessions.*' => 'history', 'logistics.sorting.*' => 'sorting',
            'logistics.deliveries.*' => 'deliveries',
        ] as $pattern => $concept) {
            if (Str::is($pattern, $route)) {
                return self::path($concept);
            }
        }

        return null;
    }
}
