<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add free_quantity column to all transaction product tables to track:
     * - Free items received from suppliers (purchase_products)
     * - Free items given to customers (sales_products)
     * - Returns of free items (purchase_return_products, sales_return_products)
     * - Transfer of free items (stock_transfer_products)
     * - Adjustment of free items (adjustment_products)
     */
    public function up(): void
    {
        // Add free_quantity to purchase_products
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });

        // Add free_quantity to sales_products
        Schema::table('sales_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });

        // Add free_quantity to purchase_return_products
        Schema::table('purchase_return_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });

        // Add free_quantity to sales_return_products
        Schema::table('sales_return_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });

        // Add free_quantity to stock_transfer_products
        Schema::table('stock_transfer_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });

        // Add free_quantity to adjustment_products
        Schema::table('adjustment_products', function (Blueprint $table) {
            $table->decimal('free_quantity', 15, 4)->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('sales_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('purchase_return_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('sales_return_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('stock_transfer_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });

        Schema::table('adjustment_products', function (Blueprint $table) {
            $table->dropColumn('free_quantity');
        });
    }
};
