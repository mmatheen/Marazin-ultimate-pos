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
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->decimal('paid_qty', 15, 4)->default(0)->after('quantity');
            $table->decimal('free_qty', 15, 4)->default(0)->after('paid_qty');
            $table->string('source_pool', 30)->nullable()->after('stock_type');
            $table->string('movement_type', 30)->nullable()->after('source_pool');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn(['paid_qty', 'free_qty', 'source_pool', 'movement_type']);
        });
    }
};
