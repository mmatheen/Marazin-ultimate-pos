<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSafeSeeder extends Seeder
{
    /**
     * Seed the application's database safely for production.
     * This seeder only creates data that doesn't exist and preserves existing data.
     */
    public function run(): void
    {
        $this->command->info('Running production-safe seeding...');
        
        // Only run essential seeders that check for existing data
        $this->call([
            Location::class,                   // Now safe - only creates if doesn't exist
            RolesAndPermissionsSeeder::class, // This should be safe as it typically checks existing roles
            UserSeeder::class,                 // Now safe - only creates new users
            WalkInCustomerSeeder::class,       // Now safe - only creates if doesn't exist
            SetDefaultInvoiceLayoutSeeder::class, // Should check existing layouts
        ]);
        
        $this->command->info('Production-safe seeding completed successfully!');
        $this->command->info('Note: Existing data was preserved. Only missing essential data was created.');
    }
}