<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            // Allows cashier to give a custom label to cash/misc items (e.g. "Paan", "Muttai Curry")
            $table->string('custom_name', 255)->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            $table->dropColumn('custom_name');
        });
    }
};
