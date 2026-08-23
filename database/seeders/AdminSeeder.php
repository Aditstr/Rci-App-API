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
        $email = trim((string) config('admin.email'));
        $password = (string) config('admin.password');

        if ($email === '') {
            $this->command->warn('Admin bootstrap dilewati: ADMIN_EMAIL belum diatur.');

            return;
        }

        $admin = User::where('email', $email)->first();

        if (! $admin) {
            if (strlen($password) < 12) {
                $this->command->warn('Admin bootstrap dilewati: ADMIN_PASSWORD minimal 12 karakter.');

                return;
            }

            $admin = User::create([
                'name' => (string) config('admin.name', 'RCI Admin'),
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'phone' => config('admin.phone'),
                'email_verified_at' => now(),
            ]);
        } else {
            // Do not reset an existing password every time the container starts.
            $admin->forceFill([
                'role' => 'admin',
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ])->save();
        }

        // PostgreSQL strict boolean: use raw SQL with TRUE/FALSE literals
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE users SET is_verified = TRUE, is_active = TRUE WHERE id = ?',
            [$admin->id]
        );

        // Ensure admin has a wallet (for platform fee collection)
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            ['balance' => '0.00']
        );

        $this->command->info("✅ Admin user ready: {$email}");
    }
}
