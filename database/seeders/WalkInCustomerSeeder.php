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
        // Always ensure walk-in customer is at ID 1 for system consistency
        $existingCustomer = DB::table('customers')->where('id', 1)->first();
        
        if (!$existingCustomer) {
            // No customer with ID 1 exists, create walk-in customer with specific ID
            DB::table('customers')->insert([
                'id' => 1,  // Force ID to be 1
                'prefix' => 'Mr.',
                'first_name' => 'Walk-in',
                'last_name' => 'Customer',
                'mobile_no' => '0000000000',  // Standard mobile number for walk-in
                'email' => 'walking customer@gmail.com',
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
            // Check if existing customer with ID 1 is already a walk-in customer
            if (stripos($existingCustomer->first_name, 'walk') !== false || 
                $existingCustomer->mobile_no === '0000000000') {
                $this->command->info('Walk-in customer already exists with ID 1.');
                
                // Update to ensure consistent data
                DB::table('customers')->where('id', 1)->update([
                    'prefix' => 'Mr.',
                    'first_name' => 'Walk-in',
                    'last_name' => 'Customer',
                    'mobile_no' => '0000000000',
                    'email' => 'walking customer@gmail.com',
                    'customer_type' => 'retailer',
                    'updated_at' => now(),
                ]);
                
                $this->command->info('Walk-in customer data standardized.');
            } else {
                // ID 1 is taken by a different customer
                $this->command->warn('WARNING: Customer ID 1 is occupied by a different customer.');
                $this->command->warn("Current ID 1: {$existingCustomer->first_name} {$existingCustomer->last_name}");
                
                // Check for other walk-in customers
                $walkInCustomers = DB::table('customers')
                    ->where(function($query) {
                        $query->whereIn('mobile_no', ['0111111111', '0000000000'])
                              ->orWhere('first_name', 'LIKE', '%walk%')
                              ->orWhere('email', 'LIKE', '%walking%');
                    })
                    ->get();
                
                if ($walkInCustomers->count() > 0) {
                    foreach ($walkInCustomers as $customer) {
                        $this->command->warn("Found walk-in customer at ID {$customer->id}: {$customer->first_name} {$customer->last_name}");
                    }
                    $this->command->info('System will use ID 1 as walk-in customer regardless.');
                } else {
                    $this->command->error('No walk-in customers found in database!');
                }
            }
        }
        
        // Clean up duplicate walk-in customers (optional)
        $duplicateWalkIns = DB::table('customers')
            ->where('id', '!=', 1)
            ->where(function($query) {
                $query->where('mobile_no', '0111111111')
                      ->orWhere(function($q) {
                          $q->where('first_name', 'Walk-in')
                            ->where('last_name', 'Customer');
                      });
            })
            ->get();
            
        if ($duplicateWalkIns->count() > 0) {
            $this->command->warn("Found {$duplicateWalkIns->count()} duplicate walk-in customer(s):");
            foreach ($duplicateWalkIns as $dup) {
                $this->command->warn("  ID {$dup->id}: {$dup->first_name} {$dup->last_name} ({$dup->mobile_no})");
            }
            $this->command->info('Consider cleaning up duplicates if they have no transaction history.');
        }
    }
}
