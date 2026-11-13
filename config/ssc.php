<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin focus-area switching
    |--------------------------------------------------------------------------
    | When true, any admin can change their own admin_type from the sidebar
    | dropdown. Set SSC_ADMIN_TYPE_SWITCHING=false in .env to lock it again
    | (the dropdown then shows the current focus area read-only and the switch
    | route returns 403). Run `php artisan config:clear` after changing .env.
    */
    'admin_type_switching' => env('SSC_ADMIN_TYPE_SWITCHING', true),

    /*
    |--------------------------------------------------------------------------
    | Admin focus areas
    |--------------------------------------------------------------------------
    | key   = value stored in users.admin_type (must match the admin_type:<key> route middleware)
    | label = text shown in the sidebar dropdown
    */
    'admin_types' => [
        'operation_building' => ['label' => 'OB Admin', 'icon' => '🏢'],
        'sports_activity'    => ['label' => 'Sports & Activity Admin', 'icon' => '🏅'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Walk-in display monitor (kiosk) at /display
    |--------------------------------------------------------------------------
    | kiosk_key:  when set (SSC_KIOSK_KEY in .env) the monitor URL needs ?key=<value>.
    | show_event_titles: false replaces event names on the public screen with "Reserved".
    */
    'kiosk_key'         => env('SSC_KIOSK_KEY'),
    'kiosk_show_event_titles' => env('SSC_KIOSK_SHOW_TITLES', true),

    /*
    |--------------------------------------------------------------------------
    | Operating hours (used for utilization % in Facility Analytics)
    |--------------------------------------------------------------------------
    | Placeholder 8 AM – 8 PM until SSC confirms the real opening hours.
    */
    'operating_hours' => ['open' => 8, 'close' => 20],

    /*
    |--------------------------------------------------------------------------
    | Post-event feedback emails (php artisan feedback:send)
    |--------------------------------------------------------------------------
    */
    'feedback' => [
        'send_after_hours'   => 1,   // wait this long after the event ends before emailing
        'reminder_after_days' => 3,  // one reminder if there's still no answer
        'window_days'        => 14,  // stop sending for events older than this
    ],

];