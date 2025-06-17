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
        Schema::create('job_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('job_ticket_no')->unique();
            $table->longText('description')->nullable();
            $table->dateTime('job_ticket_date');
            $table->string('status')->default('open'); // <-- Add status here!
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_tickets');
    }
};
