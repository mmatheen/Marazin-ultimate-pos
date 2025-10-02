<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('invoice_layout_pos', ['80mm', 'a4', 'dot_matrix'])
                  ->default('80mm')
                  ->after('mobile')
                  ->comment('Receipt layout type for POS printing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('invoice_layout_pos');
        });
    }
};