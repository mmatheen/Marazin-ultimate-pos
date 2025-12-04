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
        // Add 'sale_order' to the stock_type enum
        DB::statement("ALTER TABLE stock_histories MODIFY COLUMN stock_type ENUM(
            'opening_stock',
            'purchase', 
            'purchase_return',
            'purchase_return_reversal',
            'sale',
            'sale_reversal',
            'sale_order',
            'sale_order_reversal',
            'sales_return_with_bill',
            'sales_return_without_bill',
            'transfer_in',
            'transfer_out',
            'adjustment'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'sale_order' and 'sale_order_reversal' from the stock_type enum
        DB::statement("ALTER TABLE stock_histories MODIFY COLUMN stock_type ENUM(
            'opening_stock',
            'purchase',
            'purchase_return', 
            'purchase_return_reversal',
            'sale',
            'sale_reversal',
            'sales_return_with_bill',
            'sales_return_without_bill',
            'transfer_in',
            'transfer_out',
            'adjustment'
        ) NOT NULL");
    }
};