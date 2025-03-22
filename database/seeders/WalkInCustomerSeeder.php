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
           
        ]);
    }
}
