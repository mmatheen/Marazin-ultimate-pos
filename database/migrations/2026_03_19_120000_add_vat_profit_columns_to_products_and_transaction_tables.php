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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('original_price');
        });

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('unit_cost');
            $table->decimal('vat_per_unit', 15, 2)->default(0)->after('tax_percent');
            $table->decimal('net_unit_cost', 15, 2)->default(0)->after('vat_per_unit');
            $table->decimal('vat_total', 15, 2)->default(0)->after('total');
        });

        Schema::table('sales_products', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax');
            $table->decimal('vat_per_unit', 15, 2)->default(0)->after('tax_percent');
            $table->decimal('vat_total', 15, 2)->default(0)->after('vat_per_unit');
            $table->decimal('sale_excl_vat_per_unit', 15, 2)->default(0)->after('vat_total');
            $table->decimal('profit_per_unit', 15, 2)->default(0)->after('sale_excl_vat_per_unit');
            $table->decimal('profit_total', 15, 2)->default(0)->after('profit_per_unit');
        });

        Schema::table('purchase_return_products', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('unit_price');
            $table->decimal('vat_per_unit', 15, 2)->default(0)->after('tax_percent');
            $table->decimal('net_unit_cost', 15, 2)->default(0)->after('vat_per_unit');
            $table->decimal('vat_total', 15, 2)->default(0)->after('subtotal');
        });

        Schema::table('sales_return_products', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax');
            $table->decimal('vat_per_unit', 15, 2)->default(0)->after('tax_percent');
            $table->decimal('vat_total', 15, 2)->default(0)->after('vat_per_unit');
            $table->decimal('sale_excl_vat_per_unit', 15, 2)->default(0)->after('vat_total');
            $table->decimal('profit_per_unit', 15, 2)->default(0)->after('sale_excl_vat_per_unit');
            $table->decimal('profit_reversal_total', 15, 2)->default(0)->after('profit_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_return_products', function (Blueprint $table) {
            $table->dropColumn([
                'tax_percent',
                'vat_per_unit',
                'vat_total',
                'sale_excl_vat_per_unit',
                'profit_per_unit',
                'profit_reversal_total',
            ]);
        });

        Schema::table('purchase_return_products', function (Blueprint $table) {
            $table->dropColumn([
                'tax_percent',
                'vat_per_unit',
                'net_unit_cost',
                'vat_total',
            ]);
        });

        Schema::table('sales_products', function (Blueprint $table) {
            $table->dropColumn([
                'tax_percent',
                'vat_per_unit',
                'vat_total',
                'sale_excl_vat_per_unit',
                'profit_per_unit',
                'profit_total',
            ]);
        });

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn([
                'tax_percent',
                'vat_per_unit',
                'net_unit_cost',
                'vat_total',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tax_percent');
        });
    }
};
