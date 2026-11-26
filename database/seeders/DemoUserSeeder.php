<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * One demo account per role. Safe to run many times: an existing account
     * gets its role, password and verification reset instead of causing an error.
     *
     * Password for all three accounts: "password"
     */
    public function run(): void
    {
        $accounts = [
            ['Test User', 'test@example.com', 'admin'],
            ['Lina Librarian', 'librarian@example.com', 'librarian'],
            ['Sam Student', 'student@example.com', 'student'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // The 'hashed' cast on User hashes this plain password automatically.
                $user->forceFill([
                    'role' => $role,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ])->save();

                continue;
            }

            User::factory()->create([
                'name' => $name,
                'email' => $email,
                'role' => $role,
            ]);
        }
    }
}
