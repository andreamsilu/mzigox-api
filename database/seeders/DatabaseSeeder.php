<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Modules\Users\Models\User;
use App\Modules\Vehicles\Models\VehicleType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $vehicleTypes = [
            ['slug' => 'boda', 'name' => 'Bodaboda', 'description' => 'Motorcycle cargo', 'default_capacity_kg' => 50],
            ['slug' => 'bajaji', 'name' => 'Bajaji / Guta / Toyo', 'description' => 'Three-wheel cargo', 'default_capacity_kg' => 400],
            ['slug' => 'pickup', 'name' => 'Pickup truck', 'description' => 'Light truck', 'default_capacity_kg' => 1500],
            ['slug' => 'canter', 'name' => 'Canter', 'description' => 'Medium truck', 'default_capacity_kg' => 3500],
            ['slug' => 'lorry', 'name' => 'Lorry / Mende', 'description' => 'Heavy truck', 'default_capacity_kg' => 12000],
        ];

        foreach ($vehicleTypes as $row) {
            VehicleType::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'default_capacity_kg' => $row['default_capacity_kg'],
                    'is_active' => true,
                ]
            );
        }

        User::query()->updateOrCreate(
            ['phone' => '+255000000001'],
            [
                'full_name' => 'MzigoX Admin',
                'email' => 'admin@mzigox.local',
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'password' => bcrypt('ChangeMe!Admin'),
            ]
        );
    }
}
