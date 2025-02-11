<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WalkInCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert([
            // Default Walking Customer
            [
                'prefix' => 'Mr.',
                'first_name' => 'Walking',
                'last_name' => 'Customer',
                'mobile_no' => '0000000000',  // Placeholder number
                'email' => 'walking.customer@example.com',
                'address' => 'N/A',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'location_id' => null,  // No specific location
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Sample Customer 1
            [
                'prefix' => 'Mr.',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'mobile_no' => '9876543210',
                'email' => 'john.doe@example.com',
                'address' => '123 Main Street, New York, USA',
                'opening_balance' => 1000.00,
                'current_balance' => 1000.00,
                'location_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Sample Customer 2
            [
                'prefix' => 'Ms.',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'mobile_no' => '9876543211',
                'email' => 'jane.smith@example.com',
                'address' => '456 Elm Street, Los Angeles, USA',
                'opening_balance' => 500.00,
                'current_balance' => 500.00,
                'location_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
