<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add free_qty column to batches and location_batches tables to track paid and free stock separately.
     * This allows the POS to display: "Paid Stock: 80 | Free Stock: 18" instead of just "Total: 98"
     */
    public function up(): void
    {
        // Add free_qty to batches table
        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('free_qty', 15, 4)->default(0)->after('qty');
        });

        // Add free_qty to location_batches table
        Schema::table('location_batches', function (Blueprint $table) {
            $table->decimal('free_qty', 15, 4)->default(0)->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('free_qty');
        });

        Schema::table('location_batches', function (Blueprint $table) {
            $table->dropColumn('free_qty');
        });
    }
};
