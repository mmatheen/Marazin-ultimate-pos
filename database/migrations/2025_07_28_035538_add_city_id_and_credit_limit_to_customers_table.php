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
        Schema::table('customers', function (Blueprint $table) {
            
            $table->foreignId('city_id')->nullable()
                ->constrained('cities')->onDelete('set null');
            $table->decimal('credit_limit', 15, 2)->default(0.00)->after('current_balance');
             $table->enum('customer_type', ['wholesaler', 'retailer'])->default('retailer')->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
            $table->dropColumn('credit_limit');
            $table->dropColumn('customer_type');
        });
    }
};
