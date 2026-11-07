<?php

return [
    'regions' => [
        'NCR', 'CAR', 'Region I', 'Region II', 'Region III', 'Region IV-A', 'MIMAROPA',
        'Region V', 'Region VI', 'NIR', 'Region VII', 'Region VIII', 'Region IX',
        'Region X', 'Region XI', 'Region XII', 'Region XIII', 'BARMM',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stale parcel tracking
    |--------------------------------------------------------------------------
    |
    | The scheduled commands flag parcels and bags that have stopped moving.
    | Override these in .env without changing the schedule or the commands.
    |
    */

    'stale_parcel_hours' => (int) env('STALE_PARCEL_HOURS', 24),

    'stale_bag_hours' => (int) env('STALE_BAG_HOURS', 48),

];
