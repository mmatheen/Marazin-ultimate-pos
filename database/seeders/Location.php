<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Location extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('locations')->insert([
         
            [
                'id' => 1,
                'name' => 'Main Location',
                'location_id' => 'LOC0001',
                'address' => 'Main Location',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Kalmunai',
                'email' => 'hi@marazin.lk',
                'mobile' => '0779451959',
                'telephone_no' => '0672222257',
                'created_at' => '2025-03-15 07:55:07',
                'updated_at' => '2025-03-15 07:55:07',
            ],
            

        ]);
    }
}
