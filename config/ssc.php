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

];