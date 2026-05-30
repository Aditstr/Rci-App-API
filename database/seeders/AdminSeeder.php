<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the admin user and wallet.
     *
     * Safe to run multiple times (uses updateOrCreate).
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@rci-app.com'],
            [
                'name'              => 'RCI Admin',
                'password'          => Hash::make('RciAdmin@2026!'),
                'role'              => 'admin',
                'phone'             => '081200000000',
                'is_verified'       => true,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // Ensure admin has a wallet (for platform fee collection)
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            ['balance' => '0.00']
        );

        $this->command->info("✅ Admin user seeded: admin@rci-app.com");
    }
}
