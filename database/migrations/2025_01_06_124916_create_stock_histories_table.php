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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loc_batch_id');
            $table->integer('quantity');
            $table->enum('stock_type', ['opening_stock', 'purchase', 'purchase_return','sale', 'transfer_in', 'transfer_out']);
            $table->timestamps();

            $table->foreign('loc_batch_id')->references('id')->on('location_batches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
