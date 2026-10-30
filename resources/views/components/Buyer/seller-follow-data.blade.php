@props([
    'ids' => [],
    'counts' => [],
    'total' => null,
])

@php
    $payload = [
        'ids' => array_values(array_map('intval', $ids)),
        'counts' => (object) $counts,
        'followUrl' => route('buyer.sellers.follow', '__ID__'),
        'unfollowUrl' => route('buyer.sellers.unfollow', '__ID__'),
        'loginUrl' => route('login'),
    ];

    if ($total !== null) {
        $payload['total'] = (int) $total;
    }
@endphp

{{-- Seed data for the Alpine "follow" store. Must render before Alpine starts. --}}
<script id="sellerFollowData" type="application/json">
    @json($payload)
</script>
