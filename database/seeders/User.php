<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class User extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Get the locations' IDs for reference
          $location_id = DB::table('locations')->where('name', 'marazin')->first()->id;

        DB::table('users')->insert([
            [
                'name_title' => 'Mr',
                'name' => 'Mateen Mara',
                'user_name' => 'Matheen Maara',
                'location_id' => $location_id, // Foreign key reference
                'email' => 'matheen@gmail.com',
                'password' => Hash::make('1234'),//1234
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
