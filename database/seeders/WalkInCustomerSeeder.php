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
        // Check if walk-in customer already exists
        $existingCustomer = DB::table('customers')->where('id', 1)->first();
        
        if (!$existingCustomer) {
            // Only insert if customer doesn't exist - DO NOT update existing customers
            DB::table('customers')->insert([    
                'id' => 1,  // Fixed ID for walk-in customer
                'prefix' => 'Mr.',
                'first_name' => 'Walk-in',
                'last_name' => 'Customer',
                'mobile_no' => '0000000000',  // Placeholder number
                'email' => '',
                'address' => 'N/A',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'customer_type' => 'retailer',
                'location_id' => null,  // No specific location
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('Walk-in customer created successfully.');
        } else {
            $this->command->info('Walk-in customer already exists. Skipping to preserve existing data.');
        }
    }
}
