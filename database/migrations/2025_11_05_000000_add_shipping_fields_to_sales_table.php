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
            // Shipping details and address (optional)
            $table->text('shipping_details')->nullable()->after('payment_status');
            $table->text('shipping_address')->nullable()->after('shipping_details');
            
            // Shipping charges and status
            $table->decimal('shipping_charges', 10, 2)->default(0)->after('shipping_address');
            $table->enum('shipping_status', ['pending', 'ordered', 'shipped', 'delivered', 'cancelled'])->default('pending')->after('shipping_charges');
            
            // Delivery information (keep for future use)
            $table->string('delivered_to', 255)->nullable()->after('shipping_status');
            $table->string('delivery_person', 255)->nullable()->after('delivered_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_details',
                'shipping_address', 
                'shipping_charges',
                'shipping_status',
                'delivered_to',
                'delivery_person'
            ]);
        });
    }
};