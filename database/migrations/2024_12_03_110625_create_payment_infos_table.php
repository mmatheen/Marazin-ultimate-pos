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

        Schema::create('payment_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sell_detail_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamp('payment_date');
            $table->string('reference_num')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_mode');
            $table->enum('payment_status', ['Paid', 'Pending', 'Failed'])->default('Pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('sell_detail_id')->references('id')->on('sell_details')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_infos');
    }
};
