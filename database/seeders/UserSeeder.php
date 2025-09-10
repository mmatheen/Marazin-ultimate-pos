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
        $location_ids = $allLocations->only(['Main Location']);

        $users = [
            [
                'name_title' => 'Mr',
                'full_name' => 'Master Super Admin',
                'user_name' => 'masteradmin',
                'is_admin' => true,
                'email' => 'masteradmin@gmail.com',
                'password' => 'master1234',
                'role' => 'Master Super Admin',
                'locations' => [] // Master Super Admin has access to all locations by default
            ],
            [
                'name_title' => 'Mr',
                'full_name' => 'Super Admin',
                'user_name' => 'admin',
                'is_admin' => true,
                'email' => 'admin@gmail.com',
                'password' => '1234',
                'role' => 'Super Admin',
                'locations' => ['Main Location']
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
            if ($userData['role'] === 'Master Super Admin') {
                // Master Super Admin gets access to all locations automatically (no need to attach)
                // Location scope will be bypassed for Master Super Admin
            } elseif ($userData['role'] === 'Super Admin') {
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