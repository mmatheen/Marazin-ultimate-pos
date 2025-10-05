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
            // Check if user already exists to prevent duplicates
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $this->command->info("User {$userData['email']} already exists, skipping to preserve existing data...");
                $user = $existingUser;
                // Do not update existing user data in production
            } else {
                $this->command->info("Creating new user {$userData['email']}...");
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
            }

            // Only assign roles and locations to newly created users
            if (!$existingUser) {
                // Check if role exists before assigning
                $role = Role::where('name', $userData['role'])->first();
                if ($role) {
                    // Assign role to new user
                    $user->syncRoles([$userData['role']]);
                    // Manually sync the role_name field
                    $user->syncRoleName();
                    $this->command->info("Assigned role '{$userData['role']}' to new user {$userData['email']}");
                } else {
                    $this->command->error("Role '{$userData['role']}' not found! Please run RolesAndPermissionsSeeder first.");
                    continue;
                }

                // Attach locations via pivot table for new users only
                if ($userData['role'] === 'Master Super Admin') {
                    // Master Super Admin gets access to all locations automatically (no need to attach)
                    // Location scope will be bypassed for Master Super Admin
                    $this->command->info("Master Super Admin bypasses location scope");
                } elseif ($userData['role'] === 'Super Admin') {
                    // Super Admin gets access to all locations
                    if ($allLocations->isNotEmpty()) {
                        $user->locations()->sync($allLocations->values());
                        $this->command->info("Super Admin assigned to all locations");
                    } else {
                        $this->command->warn("No locations found in database");
                    }
                } else {
                    $locationIds = [];
                    foreach ($userData['locations'] as $locName) {
                        if (isset($location_ids[$locName])) {
                            $locationIds[] = $location_ids[$locName];
                        }
                    }
                    if (!empty($locationIds)) {
                        $user->locations()->sync($locationIds);
                        $this->command->info("User assigned to specific locations");
                    } else {
                        $this->command->warn("No matching locations found for user");
                    }
                }
            } else {
                $this->command->info("Existing user {$userData['email']} - preserving current roles and locations");
            }
        }
        
        $this->command->info("User seeding completed successfully!");
    }
}