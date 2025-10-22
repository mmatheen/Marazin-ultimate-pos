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
        Schema::table('sales', function (Blueprint $table) {
            // Sale Order Type: 'invoice' or 'sale_order'
            $table->enum('transaction_type', ['invoice', 'sale_order'])->default('invoice')->after('id');
            
            // Sale Order specific fields
            $table->string('order_number')->nullable()->unique()->after('invoice_no')
                ->comment('Unique SO number like SO-2025-0001');
            
            // Note: No sales_rep_id needed - we use existing user_id field
            // The user who creates the sale order is the sales rep
            
            $table->date('order_date')->nullable()->after('sales_date')
                ->comment('Date when order was placed');
            
            $table->date('expected_delivery_date')->nullable()->after('order_date')
                ->comment('Expected delivery/fulfillment date');
            
            $table->enum('order_status', [
                'draft',         // Initial creation
                'pending',       // Waiting for approval
                'confirmed',     // Approved by manager
                'processing',    // Being prepared
                'ready',         // Ready for delivery
                'delivered',     // Delivered to customer
                'completed',     // Converted to invoice
                'cancelled'      // Cancelled order
            ])->nullable()->after('payment_status')
                ->comment('Sale order lifecycle status');
            
            $table->foreignId('converted_to_sale_id')->nullable()->after('order_status')
                ->constrained('sales')->onDelete('set null')
                ->comment('If this is a sale order, links to the final invoice after conversion');
            
            $table->text('order_notes')->nullable()->after('converted_to_sale_id')
                ->comment('Notes about the order, delivery instructions, etc.');
            
            // Indexes for better performance
            $table->index('transaction_type');
            $table->index('order_status');
            $table->index('order_date');
            $table->index(['user_id', 'order_date']); // For sales rep reports using user_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['converted_to_sale_id']);
            
            // Drop indexes
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['order_status']);
            $table->dropIndex(['order_date']);
            $table->dropIndex(['user_id', 'order_date']);
            
            // Drop columns
            $table->dropColumn([
                'transaction_type',
                'order_number',
                'order_date',
                'expected_delivery_date',
                'order_status',
                'converted_to_sale_id',
                'order_notes'
            ]);
        });
    }
};
