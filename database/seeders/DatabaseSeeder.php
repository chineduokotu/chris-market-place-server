<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'password' => 'password',
                    'current_role' => 'seeker',
                    'is_admin' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}
