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
        Schema::table('sales_reps', function (Blueprint $table) {
            // First, drop the existing enum constraint
            DB::statement("ALTER TABLE sales_reps MODIFY COLUMN status ENUM('active', 'inactive', 'expired', 'upcoming', 'cancelled') DEFAULT 'active'");
            
            // Add indexes for efficient querying
            $table->index('status', 'idx_sales_reps_status');
            $table->index('assigned_date', 'idx_sales_reps_assigned_date');
            $table->index('end_date', 'idx_sales_reps_end_date');
            $table->index(['status', 'assigned_date'], 'idx_sales_reps_status_assigned');
            $table->index(['status', 'end_date'], 'idx_sales_reps_status_end');
            $table->index(['user_id', 'status'], 'idx_sales_reps_user_status');
            $table->index(['sub_location_id', 'status'], 'idx_sales_reps_location_status');
            $table->index(['route_id', 'status'], 'idx_sales_reps_route_status');
        });

        // Update existing 'inactive' records to 'cancelled' for better semantics
        DB::table('sales_reps')
            ->where('status', 'inactive')
            ->update(['status' => 'cancelled']);

        // Update records based on their dates
        $this->updateStatusBasedOnDates();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_reps', function (Blueprint $table) {
            // Drop the new indexes
            $table->dropIndex('idx_sales_reps_status');
            $table->dropIndex('idx_sales_reps_assigned_date');
            $table->dropIndex('idx_sales_reps_end_date');
            $table->dropIndex('idx_sales_reps_status_assigned');
            $table->dropIndex('idx_sales_reps_status_end');
            $table->dropIndex('idx_sales_reps_user_status');
            $table->dropIndex('idx_sales_reps_location_status');
            $table->dropIndex('idx_sales_reps_route_status');
            
            // Revert status enum to original values
            DB::statement("ALTER TABLE sales_reps MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            
            // Convert back any new statuses to 'inactive'
            DB::table('sales_reps')
                ->whereIn('status', ['expired', 'upcoming', 'cancelled'])
                ->update(['status' => 'inactive']);
        });
    }

    /**
     * Update existing records' status based on their assigned and end dates
     */
    private function updateStatusBasedOnDates(): void
    {
        $today = now()->toDateString();

        // Update to 'upcoming' - assignments that start in the future
        DB::table('sales_reps')
            ->where('assigned_date', '>', $today)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'upcoming']);

        // Update to 'expired' - assignments that have ended
        DB::table('sales_reps')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'expired']);

        // Update to 'active' - assignments that are currently running
        DB::table('sales_reps')
            ->where('assigned_date', '<=', $today)
            ->where(function($query) use ($today) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $today);
            })
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'active']);
    }
};
