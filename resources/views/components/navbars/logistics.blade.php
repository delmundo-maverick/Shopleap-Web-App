@props(['active' => null])
@php
    $sections = [
        'Overview' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'route' => 'logistics.dashboard',
                'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
            ],
        ],
        'Rider Management' => [
            [
                'key' => 'riders',
                'label' => 'Rider Applications',
                'route' => 'logistics.riders.index',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            ],
            [
                'key' => 'areas',
                'label' => 'Service Areas',
                'route' => 'logistics.areas.index',
                'icon' =>
                    'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
            ],
        ],
        'Parcel Operations' => [
            [
                'key' => 'pickups',
                'label' => 'Pickup Requests',
                'route' => 'logistics.pickups.index',
                'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
            ],
            [
                'key' => 'incoming',
                'label' => 'Incoming Parcels',
                'route' => 'logistics.incoming.index',
                'icon' => 'M4 12h16M4 12l6-6M4 12l6 6',
            ],
            [
                'key' => 'outbound',
                'label' => 'Outbound Transfer',
                'route' => 'logistics.outbound.index',
                'icon' => 'M5 12h14M13 6l6 6-6 6',
            ],
            [
                'key' => 'inbound',
                'label' => 'Inbound Bags',
                'route' => 'logistics.inbound.index',
                'icon' => 'M19 12H5m6 6l-6-6 6-6',
            ],
            [
                'key' => 'sorting',
                'label' => 'Sort & Assign',
                'route' => 'logistics.sorting.index',
                'icon' => 'M4 6h16M4 12h16M4 18h7',
            ],
            [
                'key' => 'sorting-sessions',
                'label' => 'Session History',
                'route' => 'logistics.sorting.sessions.index',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            ],
            [
                'key' => 'deliveries',
                'label' => 'Deliveries',
                'route' => 'logistics.deliveries.index',
                'icon' =>
                    'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            ],
        ],
        'Reports' => [
            [
                'key' => 'reports',
                'label' => 'Reports',
                'route' => 'logistics.reports.index',
                'icon' => 'M9 17v-6h6v6M4 21h16a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v16a1 1 0 001 1z',
            ],
        ],
        'General' => [
            [
                'key' => 'messages',
                'label' => 'Messages',
                'route' => 'messages.index',
                'badge' => true,
                'icon' =>
                    'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            ],
            [
                'key' => 'help',
                'label' => 'Help / Support',
                'route' => 'messages.support',
                'icon' =>
                    'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ],
    ];
@endphp
<x-nav.workspace label="Logistics Center" home="logistics.dashboard" :sections="$sections" :active="$active" class="workspace-navigation" />
