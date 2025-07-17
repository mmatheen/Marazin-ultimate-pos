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
                'email' => 'arb@gmail.com',
                'mobile' => '121212121',
                'telephone_no' => '121212121',
                'created_at' => '2025-03-15 07:55:07',
                'updated_at' => '2025-03-15 07:55:07',
            ],
            [
                'id' => 2,
                'name' => 'ARB FASHION',
                'location_id' => 'LOC0002',
                'address' => 'HIJRA JUNCTION',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Sammanthurai',
                'email' => 'arbfashion@arbtradin.lk',
                'mobile' => '777888320',
                'telephone_no' => '0672261108',
                'created_at' => '2025-03-15 08:02:08',
                'updated_at' => '2025-03-15 08:02:08',
            ],
            [
                'id' => 3,
                'name' => 'ARB SUPER CENTER',
                'location_id' => 'LOC0003',
                'address' => 'AM01ST ROAD, SAMMANTHURAI',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'SAMMANTHURAI',
                'email' => 'info@arbtrading.lk',
                'mobile' => '672261108',
                'telephone_no' => '0773445906',
                'created_at' => '2025-03-16 08:42:57',
                'updated_at' => '2025-03-16 08:42:57',
            ],

            //nithavur
            [
                'id' => 4,
                'name' => 'Ninthavur',
                'location_id' => 'LOC0004',
                'address' => 'Ninthavur',
                'province' => 'Eastern',
                'district' => 'Ampara',
                'city' => 'Ninthavur',
                'email' => 'ninthavur@arbtrading.lk',
                'mobile' => '672261108',
                'telephone_no' => '0773445906',
                'created_at' => '2025-03-16 08:42:57',
                'updated_at' => '2025-03-16 08:42:57',
            ],  


        ]);
    }
}
