<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('expenses') || !Schema::hasColumn('expenses', 'supplier_id')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });

        DB::statement('ALTER TABLE expenses MODIFY supplier_id BIGINT UNSIGNED NULL');

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('expenses') || !Schema::hasColumn('expenses', 'supplier_id')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });

        DB::statement('ALTER TABLE expenses MODIFY supplier_id BIGINT UNSIGNED NOT NULL');

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }
};
