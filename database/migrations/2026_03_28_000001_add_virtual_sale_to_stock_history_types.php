<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_histories MODIFY COLUMN stock_type ENUM(
            'opening_stock',
            'purchase',
            'purchase_return',
            'purchase_return_reversal',
            'sale',
            'virtual_sale',
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
        DB::table('stock_histories')
            ->where('stock_type', 'virtual_sale')
            ->update(['stock_type' => 'sale']);

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
};
