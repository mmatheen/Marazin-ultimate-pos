<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_backorders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_product_id');
            $table->unsignedBigInteger('location_id');

            $table->decimal('ordered_paid_qty', 15, 4)->default(0);
            $table->decimal('ordered_free_qty', 15, 4)->default(0);
            $table->decimal('fulfilled_paid_qty', 15, 4)->default(0);
            $table->decimal('fulfilled_free_qty', 15, 4)->default(0);

            $table->enum('status', ['open', 'partially_allocated', 'fully_allocated', 'fulfilled', 'cancelled'])->default('open');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sale_product_id')->references('id')->on('sales_products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');

            $table->index(['location_id', 'status'], 'idx_backorders_location_status');
            $table->index(['status', 'created_at'], 'idx_backorders_status_created');
            $table->unique(['sale_product_id', 'location_id'], 'uniq_backorder_per_sale_product_location');
        });

        Schema::create('stock_backorder_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_backorder_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_product_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->decimal('allocated_paid_qty', 15, 4)->default(0);
            $table->decimal('allocated_free_qty', 15, 4)->default(0);
            $table->enum('allocation_type', ['purchase_reservation', 'delivery_fulfillment', 'reservation_release', 'manual_adjustment'])->default('purchase_reservation');
            $table->timestamp('allocated_at')->useCurrent();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_backorder_id')->references('id')->on('stock_backorders')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('purchase_product_id')->references('id')->on('purchase_products')->onDelete('set null');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');

            $table->index(['location_id', 'allocation_type'], 'idx_backorder_alloc_location_type');
            $table->index(['purchase_id', 'purchase_product_id'], 'idx_backorder_alloc_purchase');
            $table->index(['stock_backorder_id', 'allocated_at'], 'idx_backorder_alloc_backorder_allocated_at');
        });

        Schema::table('sales_products', function (Blueprint $table) {
            $table->decimal('fulfilled_quantity', 15, 4)->default(0)->after('free_quantity');
            $table->decimal('fulfilled_free_quantity', 15, 4)->default(0)->after('fulfilled_quantity');
            $table->decimal('backordered_quantity', 15, 4)->default(0)->after('fulfilled_free_quantity');
            $table->decimal('backordered_free_quantity', 15, 4)->default(0)->after('backordered_quantity');
            $table->enum('fulfillment_status', ['pending', 'partial', 'fulfilled'])->default('pending')->after('backordered_free_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            $table->dropColumn([
                'fulfilled_quantity',
                'fulfilled_free_quantity',
                'backordered_quantity',
                'backordered_free_quantity',
                'fulfillment_status',
            ]);
        });

        Schema::dropIfExists('stock_backorder_allocations');
        Schema::dropIfExists('stock_backorders');
    }
};
