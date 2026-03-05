<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@doomshoprecords.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 1,
                'phone' => '+1-555-0100',
                'city' => 'Los Angeles',
                'street' => 'Sunset Blvd',
                'house_number' => '100',
                'zip_code' => '90001',
                'billing_phone' => '+1-555-0100',
                'billing_city' => 'Los Angeles',
                'billing_street' => 'Sunset Blvd',
                'billing_house_number' => '100',
                'billing_zip_code' => '90001',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@doomshoprecords.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('customer123'),
                'role' => 2,
                'phone' => '+1-555-0101',
                'city' => 'New York',
                'street' => 'Broadway',
                'house_number' => '200',
                'zip_code' => '10001',
                'billing_phone' => '+1-555-0101',
                'billing_city' => 'New York',
                'billing_street' => 'Broadway',
                'billing_house_number' => '200',
                'billing_zip_code' => '10001',
            ]
        );
    }
}
