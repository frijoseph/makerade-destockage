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

    // Hardening: Content-Security-Policy (set SECURITY_CSP=false to disable it while debugging) and HSTS over HTTPS.
    'security' => [
        'csp' => (bool) env('SECURITY_CSP', true),
        'hsts' => (bool) env('SECURITY_HSTS', true),
    ],

    // Assistant IA : clé API de secours (utilisée si aucune clé n'est saisie dans l'administration).
    'ai' => [
        'api_key' => env('AI_API_KEY'),
    ],

    // First back-office account, created by AdminUserSeeder (php artisan db:seed).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'email' => env('ADMIN_EMAIL', 'admin@makerade.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
