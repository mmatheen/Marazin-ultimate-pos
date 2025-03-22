<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the locations' IDs for reference
        $location_ids = DB::table('locations')->whereIn('name', ['Sammanthurai', 'ARB FASHION','ARB SUPER CENTER',])->pluck('id');

        $users = [

            // kalmunai
            [
                'name_title' => 'Mr',
                'name' => 'ARB',
                'user_name' => 'ARB',
                'is_admin' => true,
                'email' => 'arb@gmail.com',
                'password' => '1234',
                'role' => 'Super Admin',
                'location_id' => $location_ids[1] // Assign the first location ID (Kalmunai)
            ],
            
            // [
            //     'name_title' => 'Mr',
            //     'name' => 'Ahamed',
            //     'user_name' => 'Suraif',
            //     'is_admin' => false,
            //     'email' => 'suraif@arbtrading.lk',
            //     'password' => '1234',
            //     'role' => 'Admin',
            //     'location_id' => $location_ids[1] // Assign the first location ID (Colombo)
            // ],
            // [
            //     'name_title' => 'Mr',
            //     'name' => 'Mohamed',
            //     'user_name' => 'Riskan',
            //     'is_admin' => false,
            //     'email' => 'riskan@arbtrading.lk',
            //     'password' => '1234',
            //     'role' => 'Admin',
            //     'location_id' => $location_ids[1] // Assign the second location ID (Colombo)
            // ],
            // [
            //     'name_title' => 'Mr',
            //     'name' => 'Mohamed',
            //     'user_name' => 'Ajwath',
            //     'is_admin' => true,
            //     'email' => 'ajwath94@gmail.com',
            //     'password' => '1234',
            //     'role' => 'Super Admin',
            //     'location_id' => $location_ids[1] // Assign the third location ID (Colombo)
            // ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name_title' => $userData['name_title'],
                'name' => $userData['name'],
                'user_name' => $userData['user_name'],
                'is_admin' => $userData['is_admin'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']), // Encrypt password
                'location_id' => $userData['location_id'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Assign Role to User
            $user->assignRole($userData['role']);
        }
    }
}
