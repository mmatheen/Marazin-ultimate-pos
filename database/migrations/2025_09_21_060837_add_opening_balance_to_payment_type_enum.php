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
            $table->enum('payment_type', ['purchase', 'sale', 'purchase_return', 'sale_return_with_bill', 'sale_return_without_bill', 'opening_balance'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_type', ['purchase', 'sale', 'purchase_return', 'sale_return_with_bill', 'sale_return_without_bill'])->nullable()->change();
        });
    }
};
