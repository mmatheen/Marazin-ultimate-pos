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
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('default_tax_percent', 5, 2)->default(0)->after('enable_free_qty');
            $table->string('default_selling_price_tax_type', 20)->default('exclusive')->after('default_tax_percent');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('selling_price_tax_type', 20)->nullable()->after('tax_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('selling_price_tax_type');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['default_tax_percent', 'default_selling_price_tax_type']);
        });
    }
};
