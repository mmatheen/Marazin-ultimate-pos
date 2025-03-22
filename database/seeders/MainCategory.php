<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MainCategory extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $location_ids = DB::table('locations')->whereIn('name', ['Kalmunai', 'Colombo','Galle',])->pluck('id');

        DB::table('main_categories')->insert([
            [
                'mainCategoryName' => 'Buscuit',
                'description' => 'Srilankan Product',
                'location_id' => $location_ids[0],// Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'mainCategoryName' => 'Apple',
                'description' => 'IOS Version',
                'location_id' => $location_ids[0], // Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'mainCategoryName' => 'Samsung',
                'description' => 'Android Version',
                'location_id' => $location_ids[0], // Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
