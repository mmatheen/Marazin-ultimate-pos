<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds indexes for POS autocomplete and stock queries.
     * batches.product_id is usually already indexed by the foreign key.
     *
     * location_batches:
     *   - (batch_id, location_id): joins and lookups by batch/location
     *   - (location_id, qty): SUM(qty) WHERE location_id = ?
     *   - (location_id, free_qty): SUM(free_qty) WHERE location_id = ?
     */
    public function up(): void
    {
        Schema::table('location_batches', function (Blueprint $table) {
            $table->index(['batch_id', 'location_id']);
            $table->index(['location_id', 'qty']);
            if (Schema::hasColumn('location_batches', 'free_qty')) {
                $table->index(['location_id', 'free_qty']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_batches', function (Blueprint $table) {
            $table->dropIndex(['batch_id', 'location_id']);
            $table->dropIndex(['location_id', 'qty']);
            if (Schema::hasColumn('location_batches', 'free_qty')) {
                $table->dropIndex(['location_id', 'free_qty']);
            }
        });
    }
};
