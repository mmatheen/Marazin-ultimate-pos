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
            // Modify the enum to add 'dot_matrix_full' option
            DB::statement("ALTER TABLE locations MODIFY COLUMN invoice_layout_pos ENUM('80mm', 'a4', 'dot_matrix', 'dot_matrix_full') DEFAULT '80mm' COMMENT 'Receipt layout type for POS printing'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE locations MODIFY COLUMN invoice_layout_pos ENUM('80mm', 'a4', 'dot_matrix') DEFAULT '80mm' COMMENT 'Receipt layout type for POS printing'");
        });
    }
};
