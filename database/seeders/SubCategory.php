<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubCategory extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

         // Get the main categories' IDs for reference
         $mainCategoryBiscuit = DB::table('main_categories')->where('mainCategoryName', 'Buscuit')->first()->id;
         $mainCategoryApple = DB::table('main_categories')->where('mainCategoryName', 'Apple')->first()->id;
         $mainCategorySamsung = DB::table('main_categories')->where('mainCategoryName', 'Samsung')->first()->id;
         $location_id = DB::table('locations')->where('name', 'marazin')->first()->id;

        DB::table('sub_categories')->insert([
            [
                'subCategoryname' => 'Buscuit',
                'subCategoryCode' => 'Buscuit',
                'main_category_id' => $mainCategoryBiscuit, // Foreign key reference
                'description' => 'Srilankan Product',
                'location_id' => $location_id, // Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'subCategoryname' => 'Apple',
                'subCategoryCode' =>'Apple',
                'main_category_id' => $mainCategoryApple, // Foreign key reference
                'description' => 'IOS Version',
                'location_id' => $location_id, // Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'subCategoryname' => 'Samsung',
                'subCategoryCode' => 'Samsung',
                'main_category_id' => $mainCategorySamsung, // Foreign key reference
                'description' => 'Android Version',
                'location_id' => $location_id, // Foreign key reference
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

