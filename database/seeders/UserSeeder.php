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
        $location_ids = DB::table('locations')->whereIn('name', ['Kalmunai', 'Colombo','Galle',])->pluck('id');

        $users = [

            // kalmunai
            [
                'name_title' => 'Mr',
                'name' => 'Super Admin',
                'user_name' => 'Matheen Maara',
                'is_admin' => true,
                'email' => 'kalmunaisuperadmin@gmail.com',
                'password' => '1234',
                'role' => 'super-admin',
                'location_id' => $location_ids[0] // Assign the first location ID (Kalmunai)
            ],
            [
                'name_title' => 'Mr',
                'name' => 'Admin',
                'user_name' => 'Ahshan',
                'is_admin' => false,
                'email' => 'kalmunaidmin@gmail.com',
                'password' => '1234',
                'role' => 'admin',
                'location_id' => $location_ids[0] // Assign the second location ID (Colombo)
            ],
            [
                'name_title' => 'Mr',
                'name' => 'Manager',
                'user_name' => 'Aasath',
                'is_admin' => false,
                'email' => 'kalmunaimanager@gmail.com',
                'password' => '1234',
                'role' => 'manager',
                'location_id' => $location_ids[0] // Assign the third location ID (Galle)
            ],

            // colombo

            [
                'name_title' => 'Mr',
                'name' => 'Super Admin',
                'user_name' => 'Matheen Maara',
                'is_admin' => true,
                'email' => 'colombosuperadmin@gmail.com',
                'password' => '1234',
                'role' => 'super-admin',
                'location_id' => $location_ids[1] // Assign the first location ID (Colombo)
            ],
            [
                'name_title' => 'Mr',
                'name' => 'Admin',
                'user_name' => 'Ahshan',
                'is_admin' => false,
                'email' => 'colomboadmin@gmail.com',
                'password' => '1234',
                'role' => 'admin',
                'location_id' => $location_ids[1] // Assign the second location ID (Colombo)
            ],
            [
                'name_title' => 'Mr',
                'name' => 'Manager',
                'user_name' => 'Aasath',
                'is_admin' => false,
                'email' => 'colombomanager@gmail.com',
                'password' => '1234',
                'role' => 'manager',
                'location_id' => $location_ids[1] // Assign the third location ID (Colombo)
            ],
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
