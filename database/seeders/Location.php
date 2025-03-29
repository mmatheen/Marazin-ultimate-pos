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
                'id' => 1,
                'name' => 'Sammanthurai',
                'location_id' => '001',
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
            // [
            //     'id' => 2,
            //     'name' => 'ARB FASHION',
            //     'location_id' => '002',
            //     'address' => 'Colombo',
            //     'province' => 'Western',
            //     'district' => 'Colombo',
            //     'city' => 'Colombo',
            //     'email' => 'colombo.com',
            //     'mobile' => '0771234567',
            //     'telephone_no' => '0112345678',
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ],
            // [
            //     'id' => 3,
            //     'name' => 'ARB SUPER CENTER',
            //     'location_id' => '003',
            //     'address' => 'Galle',
            //     'province' => 'Southern',
            //     'district' => 'Galle',
            //     'city' => 'Galle',
            //     'email' => 'galle.com',
            //     'mobile' => '0782345678',
            //     'telephone_no' => '0912345678',
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ],

        ]);
    }
}
