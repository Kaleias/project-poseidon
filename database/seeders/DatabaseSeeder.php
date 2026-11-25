<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // One demo account per role (password for all: "password")
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Lina Librarian',
            'email' => 'librarian@example.com',
            'role' => 'librarian',
        ]);

        User::factory()->create([
            'name' => 'Sam Student',
            'email' => 'student@example.com',
            'role' => 'student',
        ]);

        $this->call([
            CategorySeeder::class,
            MemberSeeder::class,
            BookSeeder::class,
        ]);
    }
}
