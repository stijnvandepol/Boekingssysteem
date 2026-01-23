<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make('ChangeMe123!'),
            ]
        );

        Resource::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'name' => 'Rens Booking',
                'timezone' => 'Europe/Amsterdam',
                'default_slot_length_minutes' => 30,
                'default_capacity' => 1,
                'is_active' => true,
            ]
        );
    }
}
