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
            'prefix' => 'Mr.',
            'first_name' => 'Walk-In',
            'last_name' => 'Customer',
            'mobile_no' => '0000000000',
            'email' => 'walkin.customer@example.com',
            'contact_id' => 'WALKIN001',
            'contact_type' => 'individual',
            'date' => Carbon::now()->toDateString(),
            'assign_to' => 'Admin',
            'opening_balance' => 0.00,
            // 'location_id' => 2,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
