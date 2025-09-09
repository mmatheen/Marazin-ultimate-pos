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
        Schema::table('roles', function (Blueprint $table) {
            // Add key column: canonical identifier (e.g. 'sales_rep', 'admin')
            $table->string('key')->nullable()->unique()->after('name');
        });

        // Optional: Index for performance
        Schema::table('roles', function (Blueprint $table) {
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->dropColumn('key');
        });
    }
};
