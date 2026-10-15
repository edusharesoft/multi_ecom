<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'branch'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'branches'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        // Branch managers log in with this guard
        'branch' => [
            'driver' => 'session',
            'provider' => 'branches',
        ],
        // Storefront customers (scoped to one branch — see App\Support\StoreCustomer)
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
        // Branch staff (role based permissions)
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
        'branches' => [
            'driver' => 'eloquent',
            'model' => App\Models\Branch::class,
        ],
        'staff' => [
            'driver' => 'eloquent',
            'model' => App\Models\Staff::class,
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Customer::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'branches' => [
            'provider' => 'branches',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
