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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id(); // Ensure this is an unsignedBigInteger
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('reference_no')->unique();
            $table->date('purchase_date');
            $table->enum('purchasing_status', ['Received', 'Pending', 'Ordered'])->default('Pending');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->integer('pay_term')->nullable();
            $table->enum('pay_term_type', ['days', 'months'])->nullable();
            $table->string('attached_document')->nullable();
            $table->decimal('total', 15, 2);
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_total', 15, 2);
            $table->enum('payment_status', ['Paid', 'Due', 'Partial'])->default('Due');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
