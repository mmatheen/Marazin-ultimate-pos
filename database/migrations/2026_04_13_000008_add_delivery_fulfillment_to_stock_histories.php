<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'delivery_fulfillment' stock type for backorder delivery fulfillment tracking.
     * This is the only place stock is deducted when fulfilling backorders at delivery time.
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
            'adjustment',
            'delivery_fulfillment'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::table('stock_histories')
            ->where('stock_type', 'delivery_fulfillment')
            ->delete();

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
};
