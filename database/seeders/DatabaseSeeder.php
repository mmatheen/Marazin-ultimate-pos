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
        $this->call(Location::class);
        $this->call(MainCategory::class);
        $this->call(SubCategory::class);
        $this->call(Role::class);
        $this->call(permission::class);
        $this->call(RoleHasPermission::class);
        $this->call(User::class);
    }
}
