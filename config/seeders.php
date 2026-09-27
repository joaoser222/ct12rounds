<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Seeded administrator
    |--------------------------------------------------------------------------
    |
    | Optional credentials used by UserSeeder. When ADMIN_PASSWORD is empty the
    | seeder does nothing, so a fresh install never ships a known password.
    | The role and the permissions are assigned by `access-control:sync`, which
    | owns that data; run it after seeding.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@ct12rounds.com'),
        'password' => env('ADMIN_PASSWORD', ''),
    ],
];
