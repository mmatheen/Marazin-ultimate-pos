<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->unique();
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->timestamps();

            // Optional: Foreign key constraint
            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_counters');
    }
}