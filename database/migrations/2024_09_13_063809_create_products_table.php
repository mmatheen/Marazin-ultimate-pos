<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
       public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('sku')->unique();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->boolean('stock_alert')->nullable();
            $table->integer('alert_quantity')->nullable();
            $table->string('product_image')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_imei_or_serial_no')->nullable();
            $table->string('is_for_selling')->nullable();
            $table->string('product_type');
            $table->string('pax')->nullable();
            $table->double('original_price');
            $table->double('retail_price');
            $table->double('whole_sale_price');
            $table->double('special_price');
            $table->double('max_retail_price');
            $table->timestamps();

            // Foreign keys
            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('main_category_id')->references('id')->on('main_categories');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories');
        });

        Schema::create('location_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            $table->integer('qty')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_product');
        Schema::dropIfExists('products');
    }
};

