<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->json('receipt_config')->nullable()->after('footer_note');
        });

        // Set default config for all existing locations
        $defaultConfig = json_encode([
            'show_logo' => true,
            'show_customer_phone' => true,
            'show_mrp_strikethrough' => true,
            'show_imei' => true,
            'show_discount_breakdown' => true,
            'show_payment_method' => true,
            'show_outstanding_due' => true,
            'show_stats_section' => true,
            'show_footer_note' => true,
            'spacing_mode' => 'compact',
            'font_size_base' => 11,
            'line_spacing' => 5,
        ]);

        DB::table('locations')->update(['receipt_config' => $defaultConfig]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('receipt_config');
        });
    }
};
