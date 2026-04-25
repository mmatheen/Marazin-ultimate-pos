<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
                'sale_dues',
                'both',
                'advance',
                'advance_credit_usage', // Credit applied to invoices (no new cash collected)
            ])->nullable()->change();
        });
    }

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
                'recovery',
                'sale_dues',
                'both',
                'advance',
            ])->nullable()->change();
        });
    }
};

