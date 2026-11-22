<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the first back-office account from ADMIN_EMAIL / ADMIN_PASSWORD (see .env.example).
 * Change the password from the admin once logged in.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('shop.admin.email');

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $password = (string) config('shop.admin.password');

        if (app()->isProduction() && (strlen($password) < 12 || in_array(strtolower($password), ['password', 'admin', 'changeme', '12345678'], true))) {
            throw new RuntimeException('En production, définissez ADMIN_PASSWORD (12 caractères minimum, pas « password ») avant de créer le compte administrateur.');
        }

        User::query()->create([
            'name' => config('shop.admin.name'),
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
