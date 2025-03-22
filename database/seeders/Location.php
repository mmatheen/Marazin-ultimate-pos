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
                'place_id' => 'LOC001',
                'address' => 'Sammanthurai',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Kalmunai',
                'email' => 'arb@gmail.com',
                'mobile' => '0757571411',
                'telephone_no' => '0757571411',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'name' => 'ARB FASHION',
                'place_id' => 'LOC001',
                'address' => 'HIJRA JUNCTION',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Sammanthurai',
                'email' => 'arbfashion@arbtrading.com',
                'mobile' => '0777888320',
                'telephone_no' => '0672261108',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'name' => 'ARB SUPER CENTER',
                'place_id' => 'LOC0003',
                'address' => 'AM01ST Road,Sammanthurai',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Sammanthurai',
                'email' => 'info@arbtrading.com',
                'mobile' => '0773445906',
                'telephone_no' => '0672261108',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ]);
    }
}
