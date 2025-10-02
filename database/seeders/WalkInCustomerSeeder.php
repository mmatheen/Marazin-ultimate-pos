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
        // Use updateOrInsert to handle duplicates gracefully
        DB::table('customers')->updateOrInsert(
            // Search criteria - if any of these match, update instead of insert
            ['mobile_no' => '0111111111'],
            // Data to insert or update
            [
                'prefix' => 'Mr.',
                'first_name' => 'Walk-in',
                'last_name' => 'Customer',
                'mobile_no' => '0111111111',  // Placeholder number
                'email' => '',
                'address' => 'N/A',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'location_id' => null,  // No specific location
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        $this->command->info('Walk-in customer processed successfully.');
    }
}
