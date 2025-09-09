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
        Schema::create('sales_rep_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('sales_reps')->onDelete('cascade');
            $table->decimal('target_amount', 15, 2)->default(0.00);
            $table->decimal('achieved_amount', 15, 2)->default(0.00);
            $table->date('target_month');
            $table->timestamps();
            $table->unique(['sales_rep_id', 'target_month'], 'unique_target_per_month');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_rep_targets');
    }
};
