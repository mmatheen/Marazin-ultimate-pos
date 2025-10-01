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
        if (!Schema::hasTable('supplier_balance_logs')) {
            Schema::create('supplier_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->enum('transaction_type', [
                'expense_overpayment', 
                'expense_edit', 
                'payment_adjustment', 
                'payment_edit',
                'payment_delete',
                'manual_adjustment'
            ]);
            $table->decimal('amount', 15, 2); // Positive = Credit to supplier, Negative = Debit from supplier
            $table->enum('debit_credit', ['debit', 'credit']); // credit = we owe supplier, debit = supplier owes us
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description');
            $table->json('metadata')->nullable(); // Store additional data like old/new amounts
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('expense_payments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['supplier_id', 'created_at']);
            $table->index(['expense_id']);
            $table->index(['payment_id']);
            $table->index(['transaction_type']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_balance_logs');
    }
};