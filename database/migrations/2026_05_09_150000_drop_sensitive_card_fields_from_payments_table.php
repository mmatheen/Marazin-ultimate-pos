<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove CVV and card expiry from persistent storage (PCI / privacy).
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $drop = [];
            foreach (['card_expiry_month', 'card_expiry_year', 'card_security_code'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'card_expiry_month')) {
                $table->string('card_expiry_month')->nullable();
            }
            if (! Schema::hasColumn('payments', 'card_expiry_year')) {
                $table->string('card_expiry_year')->nullable();
            }
            if (! Schema::hasColumn('payments', 'card_security_code')) {
                $table->string('card_security_code')->nullable();
            }
        });
    }
};
