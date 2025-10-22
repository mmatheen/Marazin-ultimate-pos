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
        Schema::table('purchase_products', function (Blueprint $table) {
            // Add price column (original price before discount)
            $table->decimal('price', 15, 2)->default(0)->after('quantity')->comment('Original price before discount');
            
            // Add discount_percent column
            $table->decimal('discount_percent', 5, 2)->default(0)->after('price')->comment('Product-level discount percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn(['price', 'discount_percent']);
        });
    }
};
