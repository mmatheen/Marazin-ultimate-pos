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
        Schema::create('bulk_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('action', ['edit', 'delete']); // Action performed
            $table->enum('entity_type', ['sale', 'purchase']); // Sale or Purchase
            $table->unsignedBigInteger('payment_id'); // Original payment ID
            $table->unsignedBigInteger('entity_id')->nullable(); // Sale/Purchase ID
            $table->unsignedBigInteger('customer_id')->nullable(); // Customer ID for sales
            $table->unsignedBigInteger('supplier_id')->nullable(); // Supplier ID for purchases
            $table->json('old_data')->nullable(); // Store old payment data before edit/delete
            $table->json('new_data')->nullable(); // Store new payment data after edit (null for delete)
            $table->decimal('old_amount', 15, 2); // Original payment amount
            $table->decimal('new_amount', 15, 2)->nullable(); // New payment amount (null for delete)
            $table->string('reference_no')->nullable(); // Payment reference number
            $table->text('reason')->nullable(); // Reason for edit/delete
            $table->unsignedBigInteger('performed_by'); // User who performed the action
            $table->timestamp('performed_at'); // When the action was performed
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_payment_logs');
    }
};
