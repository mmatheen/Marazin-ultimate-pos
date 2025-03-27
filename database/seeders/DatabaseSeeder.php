<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // you have to add this according to your forignkey table order
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(Location::class);
        $this->call(UserSeeder::class);
        // $this->call(MainCategory::class);
        // $this->call(SubCategory::class);
        $this->call([WalkInCustomerSeeder::class,
        ]);


    }
}
