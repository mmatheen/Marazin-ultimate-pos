<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'id'=>1,
                'name' => 'marazin',
                'Location_id'=>'',
                'address' => 'Kalmunai',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Kalmunai',
                'email' => 'matheen@gmail.com',
                'mobile' => '0757571411',
                'telephone_no' => '0757571411',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ]);
    }
}
