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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->date('return_date');
            $table->decimal('return_total', 12, 2);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2)
                ->generatedAs('return_total - total_paid')
                ->stored();  // Dynamic calculation of total_due
            $table->enum('payment_status', ['Paid', 'Partial', 'Due'])->default('Due');
            $table->string('attach_document')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
