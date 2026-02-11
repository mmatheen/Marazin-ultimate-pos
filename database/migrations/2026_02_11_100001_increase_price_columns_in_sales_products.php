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
        Schema::table('sales_products', function (Blueprint $table) {
            // Increase price column from decimal(8,2) to decimal(15,2) to support larger prices
            // Old max: 999,999.99
            // New max: 9,999,999,999,999.99
            $table->decimal('price', 15, 2)->change();
            
            // Also increase discount_amount and tax for consistency
            $table->decimal('discount_amount', 15, 2)->nullable()->change();
            $table->decimal('tax', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            // Revert back to original size
            $table->decimal('price', 8, 2)->change();
            $table->decimal('discount_amount', 8, 2)->nullable()->change();
            $table->decimal('tax', 8, 2)->nullable()->change();
        });
    }
};
