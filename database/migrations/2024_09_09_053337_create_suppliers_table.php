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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile_no');
            $table->string('email');
            $table->string('contact_id');
            $table->string('contact_type');
            $table->string('date');
            $table->string('assign_to');
            $table->double('opening_balance');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();

             // ForeignKey
             $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
