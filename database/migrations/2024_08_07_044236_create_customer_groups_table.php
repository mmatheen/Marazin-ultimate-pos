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
        Schema::create('customer_groups', function (Blueprint $table) {

            $table->id();
            $table->string('customerGroupName');
            $table->string('priceCalculationType');
            $table->string('calculationPercentage')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('selling_price_group_id')->nullable();
            $table->timestamps();

            // ForeignKey
            $table->foreign('selling_price_group_id')->references('id')->on('selling_price_groups');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
