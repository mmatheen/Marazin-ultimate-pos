<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('locations')->onDelete('cascade');
            $table->string('vehicle_number')->nullable()->unique();
            $table->string('vehicle_type')->nullable()->after('vehicle_number');
            
            // Add index for better performance on vehicle queries
            $table->index(['parent_id', 'vehicle_number'], 'locations_parent_vehicle_index');
        });

        // Add check constraint to ensure sublocations have vehicle details (MySQL 8+ / PostgreSQL)
        DB::statement('
            ALTER TABLE locations 
            ADD CONSTRAINT chk_sublocation_vehicle_details 
            CHECK (
                parent_id IS NULL OR 
                (parent_id IS NOT NULL AND vehicle_number IS NOT NULL AND vehicle_type IS NOT NULL)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Drop check constraint safely
            try {
                DB::statement('ALTER TABLE locations DROP CONSTRAINT IF EXISTS chk_sublocation_vehicle_details');
            } catch (\Exception $e) {
                // ignore if not exists
            }

            // Drop indexes
            $table->dropIndex('locations_parent_vehicle_index');

            // Drop unique constraint on vehicle_number
            $table->dropUnique('locations_vehicle_number_unique');

            // Drop columns
            $table->dropColumn(['vehicle_number', 'vehicle_type']);

            // Drop foreign key and column
            $table->dropForeign(['parent_id']); 
            $table->dropColumn('parent_id');    
        });
    }
};
