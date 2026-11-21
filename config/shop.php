<?php

/*
|--------------------------------------------------------------------------
| Makerade — environment-level options
|--------------------------------------------------------------------------
|
| Shop content and business values (currency, shipping, texts, menus…) are
| NOT here: they live in the settings table, are described in
| App\Support\SettingsSchema and are edited from /admin/settings.
|
*/

return [

    // Read the storefront catalogue from MySQL instead of the bundled data.
    'catalog_from_db' => (bool) env('CATALOG_USE_DB', false),

    // First back-office account, created by AdminUserSeeder (php artisan db:seed).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'email' => env('ADMIN_EMAIL', 'admin@makerade.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
