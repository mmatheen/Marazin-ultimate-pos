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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('sales_date');
            $table->enum('sale_type', ['POS', 'Normal'])->default('Normal'); // Added sale_type field
            $table->string('status');
            $table->string('invoice_no')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_total', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            // Using generated column for total_due
            $table->decimal('total_due', 15, 2)
                  ->generatedAs('final_total - total_paid')
                  ->stored(); // Store the result

            $table->enum('payment_status', ['Paid', 'Partial', 'Due'])->default('Due');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
