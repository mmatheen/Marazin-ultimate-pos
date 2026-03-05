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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->after('reference_id')
                    ->constrained('cash_registers')->onDelete('set null');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cash_register_id')) {
                $table->index('cash_register_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cash_register_id')) {
                $table->dropIndex(['cash_register_id']);
                $table->dropForeign(['cash_register_id']);
            }
        });
    }
};
