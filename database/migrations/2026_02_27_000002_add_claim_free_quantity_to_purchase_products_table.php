<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            // Qty the supplier PROMISED but did NOT deliver at purchase time — to be claimed later
            $table->decimal('claim_free_quantity', 15, 4)
                  ->default(0)
                  ->after('free_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn('claim_free_quantity');
        });
    }
};
