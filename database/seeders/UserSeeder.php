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
        // Get all locations and their IDs
        $allLocations = DB::table('locations')->pluck('id', 'name');
        $location_ids = $allLocations->only(['Sammanthurai', 'ARB FASHION', 'ARB SUPER CENTER', 'Ninthavur']);

        $users = [
            [
                'name_title' => 'Mr',
                'full_name' => 'ARB',
                'user_name' => 'ARB',
                'is_admin' => true,
                'email' => 'arb@gmail.com',
                'password' => '1234',
                'role' => 'Super Admin',
                'locations' => ['Sammanthurai']
            ],
            [
                'name_title' => 'Mr',
                'full_name' => 'Apple Bees',
                'user_name' => 'AppleBees',
                'is_admin' => true,
                'email' => 'applebees@gmail.com',
                'password' => '1234',
                'role' => 'Super Admin',
                'locations' => ['Ninthavur']
            ],
            [
                'name_title' => 'Mr',
                'full_name' => 'Ahamed',
                'user_name' => 'Suraif',
                'is_admin' => false,
                'email' => 'suraif@arbtrading.lk',
                'password' => '1234',
                'role' => 'Admin',
                'locations' => ['ARB FASHION']
            ],
            [
                'name_title' => 'Mr',
                'full_name' => 'Mohamed',
                'user_name' => 'Riskan',
                'is_admin' => false,
                'email' => 'riskan@arbtrading.lk',
                'password' => '1234',
                'role' => 'Admin',
                'locations' => ['ARB FASHION']
            ],
            [
                'name_title' => 'Mr',
                'full_name' => 'Mohamed',
                'user_name' => 'Ajwath',
                'is_admin' => false,
                'email' => 'ajwath94@gmail.com',
                'password' => '1234',
                'role' => 'Super Admin',
                'locations' => ['ARB SUPER CENTER']
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name_title' => $userData['name_title'],
                'full_name' => $userData['full_name'],
                'user_name' => $userData['user_name'],
                'is_admin' => $userData['is_admin'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Assign Role to User
            $user->assignRole($userData['role']);

            // Attach locations via pivot table
            if ($userData['role'] === 'Super Admin') {
                // Super Admin gets access to all locations
                $user->locations()->attach($allLocations->values());
            } else {
                $locationIds = [];
                foreach ($userData['locations'] as $locName) {
                    if (isset($location_ids[$locName])) {
                        $locationIds[] = $location_ids[$locName];
                    }
                }
                if (!empty($locationIds)) {
                    $user->locations()->attach($locationIds);
                }
            }
        }
    }
}