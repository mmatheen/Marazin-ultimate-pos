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
                'name' => 'Sammanthurai',
                'location_id' => 'LOC0001',
                'address' => 'Sammanthurai',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Sammanthurai',
                'email' => 'sam@gmail.com',
                'mobile' => '121212121',
                'telephone_no' => '121212121',
                'created_at' => '2025-03-15 07:55:07',
                'updated_at' => '2025-03-15 07:55:07',
            ],
            

        ]);
    }
}
