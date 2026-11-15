@props(['active' => null])
@php
    $sections = [
        'Overview' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'route' => 'logistics.dashboard',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.dashboard'),
            ],
        ],
        'Rider Management' => [
            [
                'key' => 'rider-rate',
                'label' => 'Rider Rate',
                'route' => 'logistics.rider-rate.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.rider-rate.index'),
            ],
            [
                'key' => 'riders',
                'label' => 'Rider Applications',
                'route' => 'logistics.riders.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.riders.index'),
            ],
            [
                'key' => 'areas',
                'label' => 'Service Areas',
                'route' => 'logistics.areas.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.areas.index'),
            ],
        ],
        'Parcel Operations' => [
            [
                'key' => 'pickups',
                'label' => 'Pickup Requests',
                'route' => 'logistics.pickups.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.pickups.index'),
            ],
            [
                'key' => 'incoming',
                'label' => 'Incoming Parcels',
                'route' => 'logistics.incoming.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.incoming.index'),
            ],
            [
                'key' => 'outbound',
                'label' => 'Outbound Transfer',
                'route' => 'logistics.outbound.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.outbound.index'),
            ],
            [
                'key' => 'inbound',
                'label' => 'Inbound Bags',
                'route' => 'logistics.inbound.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.inbound.index'),
            ],
            [
                'key' => 'sorting',
                'label' => 'Sort & Assign',
                'route' => 'logistics.sorting.index',
                'pattern' => 'logistics.sorting.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.sorting.index'),
            ],
            [
                'key' => 'sorting-sessions',
                'label' => 'Session History',
                'route' => 'logistics.sorting.sessions.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.sorting.sessions.index'),
            ],
            [
                'key' => 'deliveries',
                'label' => 'Deliveries',
                'route' => 'logistics.deliveries.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.deliveries.index'),
            ],
        ],
        'Money' => [
            ['route' => 'logistics.payouts.index', 'label' => 'Rider payouts'],
            ['route' => 'logistics.movements.index', 'params' => ['section' => 'cash'], 'label' => 'Rider cash', 'selected' => request()->routeIs('logistics.movements.index') && request()->route('section') === 'cash'],
            ['route' => 'logistics.movements.index', 'params' => ['section' => 'remittances'], 'label' => 'Remittance', 'selected' => request()->routeIs('logistics.movements.index') && request()->route('section') === 'remittances'],
        ],
        'Reports' => [
            [
                'key' => 'reports',
                'label' => 'Reports',
                'route' => 'logistics.reports.index',
                'icon' => \App\Support\NavigationIcons::forRoute('logistics.reports.index'),
            ],
        ],
        'General' => [
            [
                'key' => 'messages',
                'label' => 'Messages',
                'route' => 'messages.index',
                'badge' => true,
                'icon' => \App\Support\NavigationIcons::forRoute('messages.index'),
            ],
            [
                'key' => 'help',
                'label' => 'Help / Support',
                'route' => 'messages.support',
                'icon' => \App\Support\NavigationIcons::forRoute('messages.support'),
            ],
        ],
    ];
@endphp
<x-nav.workspace label="Logistics Center" home="logistics.dashboard" :sections="$sections" :active="$active" class="workspace-navigation" />
