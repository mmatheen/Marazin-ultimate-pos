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
        // Ensure walk-in customer always has ID 1 for system consistency
        $existingCustomer = DB::table('customers')->where('id', 1)->first();
        
        if (!$existingCustomer) {
            // No customer with ID 1 exists, create walk-in customer with specific ID
            DB::table('customers')->insert([
                'id' => 1,  // Force ID to be 1
                'prefix' => 'Mr.',
                'first_name' => 'Walk-in',
                'last_name' => 'Customer',
                'mobile_no' => '0111111111',  // Placeholder number
                'email' => '',
                'address' => 'N/A',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'location_id' => null,  // No specific location
                'city_id' => null,
                'credit_limit' => 0.00,
                'customer_type' => 'retailer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('Walk-in customer created successfully with ID 1.');
        } else {
            // Check if existing customer with ID 1 is the walk-in customer
            if ($existingCustomer->mobile_no === '0111111111' && 
                $existingCustomer->first_name === 'Walk-in') {
                $this->command->info('Walk-in customer already exists with ID 1.');
            } else {
                // ID 1 is taken by a different customer - this is a problem
                $this->command->warn('WARNING: Customer ID 1 is occupied by a different customer. Walk-in customer should have ID 1.');
                
                // Check if walk-in customer exists with different ID
                $walkInCustomer = DB::table('customers')->where('mobile_no', '0111111111')->first();
                if ($walkInCustomer) {
                    $this->command->warn("Walk-in customer found with ID {$walkInCustomer->id}. This may cause POS system issues.");
                } else {
                    // Create walk-in customer with next available ID (not ideal but functional)
                    DB::table('customers')->insert([
                        'prefix' => 'Mr.',
                        'first_name' => 'Walk-in',
                        'last_name' => 'Customer',
                        'mobile_no' => '0111111111',
                        'email' => '',
                        'address' => 'N/A',
                        'opening_balance' => 0.00,
                        'current_balance' => 0.00,
                        'location_id' => null,
                        'city_id' => null,
                        'credit_limit' => 0.00,
                        'customer_type' => 'retailer',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->command->warn('Walk-in customer created with auto-generated ID (not ID 1).');
                }
            }
        }
    }
}
