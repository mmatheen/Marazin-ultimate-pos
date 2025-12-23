<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds sale_notes field to sales table for sale-level notes/references
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->text('sale_notes')->nullable()->after('payment_status')
                ->comment('Sale notes, description, or reference for this sale transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('sale_notes');
        });
    }
};
