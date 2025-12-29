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
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_type', [
                'purchase',
                'sale',
                'purchase_return',
                'sale_return_with_bill',
                'sale_return_without_bill',
                'opening_balance',
                'recovery',
                'sale_dues',        // For bulk payment - pay only sale dues
                'both',             // For bulk payment - pay both opening balance + sale dues
                'advance',          // For advance payments (overpayment)
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_type', [
                'purchase',
                'sale',
                'purchase_return',
                'sale_return_with_bill',
                'sale_return_without_bill',
                'opening_balance',
                'recovery'
            ])->nullable()->change();
        });
    }
};
