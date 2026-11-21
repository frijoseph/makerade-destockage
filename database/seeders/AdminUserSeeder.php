<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

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

        User::query()->create([
            'name' => config('shop.admin.name'),
            'email' => $email,
            'password' => config('shop.admin.password'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
