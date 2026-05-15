<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Realtime Database — operational layer only
    |--------------------------------------------------------------------------
    |
    | Laravel + PostgreSQL remain business truth. These nodes hold transient
    | live data for mobile subscriptions (GPS, presence, trip progress, ETA).
    |
    | Driver apps SHOULD write GPS directly to RTDB every 3–5 seconds.
    | Laravel syncs authoritative trip status changes via queued jobs.
    |
    */

    'nodes' => [
        'drivers' => 'drivers/{driver_id}',
        'trips' => 'trips/{trip_id}',
    ],

    /** Recommended driver GPS write interval (seconds) for mobile clients. */
    'driver_gps_interval_seconds' => (int) env('FIREBASE_DRIVER_GPS_INTERVAL', 4),

    'driver_fields' => [
        'lat',
        'lng',
        'presence',
        'vehicle_type',
        'updated_at',
    ],

    'trip_fields' => [
        'status',
        'eta',
        'driver_location',
        'progress',
        'updated_at',
    ],

    'forbidden_fields' => [
        'wallet_balance',
        'commission',
        'payment',
        'price',
        'estimated_price',
        'final_price',
    ],

];
