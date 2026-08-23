<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bootstrap Administrator
    |--------------------------------------------------------------------------
    |
    | These values are only used by AdminSeeder to create the first admin.
    | An existing admin password is never overwritten during container startup.
    |
    */

    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
    'name' => env('ADMIN_NAME', 'RCI Admin'),
    'phone' => env('ADMIN_PHONE'),
];
