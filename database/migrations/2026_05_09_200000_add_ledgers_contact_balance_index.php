<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speeds up BalanceHelper::getCustomerBalance / supplier balance aggregates.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ledgers')) {
            return;
        }

        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'contact_id')
                && Schema::hasColumn('ledgers', 'contact_type')
                && Schema::hasColumn('ledgers', 'status')) {
                $table->index(['contact_id', 'contact_type', 'status'], 'ledgers_contact_balance_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ledgers')) {
            return;
        }

        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropIndex('ledgers_contact_balance_idx');
        });
    }
};
