<?php

/*
|--------------------------------------------------------------------------
| Makerade shop defaults
|--------------------------------------------------------------------------
|
| Default values for the shop. Every key except "catalog_from_db" can be
| overridden from the admin "Paramètres" screen (stored in the settings table).
|
*/

return [

    // Read the storefront catalogue from MySQL instead of the bundled data.
    'catalog_from_db' => (bool) env('CATALOG_USE_DB', false),

    'shop_name' => env('APP_NAME', 'Makerade Destockage'),
    'contact_email' => 'contact@makerade-destockage.com',
    'contact_phone' => '+225 07 00 00 00 00',
    'whatsapp_number' => '',

    'currency_symbol' => '$',
    'currency_position' => 'before',
    'currency_decimals' => 2,

    'shipping_fee' => 4.99,
    'free_shipping_threshold' => 49,
    'low_stock_threshold' => 10,

    // First back-office account, created by AdminUserSeeder (php artisan db:seed).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'email' => env('ADMIN_EMAIL', 'admin@makerade.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
