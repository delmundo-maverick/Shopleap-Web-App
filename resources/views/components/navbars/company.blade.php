@php
    $sections = ['Company' => [
        ['route' => 'company.dashboard', 'label' => 'Dashboard', 'selected' => request()->routeIs('company.dashboard', 'company.attention')],
        ['route' => 'company.hubs.index', 'label' => 'Hubs'],
        ['route' => 'company.map', 'label' => 'Hub map'],
        ['route' => 'company.managers.index', 'label' => 'Managers'],
        ['route' => 'company.coverage', 'label' => 'Coverage'],
        ['route' => 'company.transfers.index', 'label' => 'Transfers'],
        ['route' => 'company.reports', 'label' => 'Reports'],
        ['route' => 'company.finance', 'label' => 'Finance'],
        ['route' => 'company.activity-log', 'label' => 'Activity log'],
        ['route' => 'company.announcements.index', 'label' => 'Announcements'],
        ['route' => 'company.settings', 'label' => 'Settings'],
    ]];
@endphp
<x-nav.workspace label="Logistics Company" home="company.dashboard" :sections="$sections" class="workspace-navigation" />
