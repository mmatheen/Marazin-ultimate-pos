<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * - imei_numbers: (product_id, location_id) for POS autocomplete IMEI filter
     * - products: product_name for LIKE search (sku already unique/indexed)
     */
    public function up(): void
    {
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->index(['product_id', 'location_id']);
        });

        if (! Schema::hasTable('products')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $idx = DB::select("SHOW INDEX FROM products WHERE Column_name = 'product_name' AND Key_name != 'PRIMARY'");
            if (empty($idx)) {
                Schema::table('products', function (Blueprint $table) {
                    $table->index('product_name');
                });
            }
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->index('product_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'location_id']);
        });

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['product_name']);
            });
        }
    }
};
