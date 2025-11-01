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
        Schema::table('payments', function (Blueprint $table) {
            // Recovery chain tracking
            if (!Schema::hasColumn('payments', 'recovery_for_payment_id')) {
                $table->foreignId('recovery_for_payment_id')->nullable()->after('reference_id')
                      ->constrained('payments')->onDelete('set null')
                      ->comment('Links recovery payment to original bounced payment');
            }
            
            // Bank transfer details
            if (!Schema::hasColumn('payments', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('cheque_given_by');
            }
            
            // Card type for better tracking
            if (!Schema::hasColumn('payments', 'card_type')) {
                $table->enum('card_type', ['visa', 'mastercard', 'amex', 'other'])->nullable()->after('card_security_code');
            }
            
            // Enhanced reference handling
            if (!Schema::hasColumn('payments', 'actual_payment_method')) {
                $table->string('actual_payment_method')->nullable()->after('payment_method')
                      ->comment('Original payment method when using recovery methods');
            }
            
            // User tracking
            if (!Schema::hasColumn('payments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('payment_status')
                      ->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')
                      ->constrained('users')->onDelete('set null');
            }
        });
        
        // Add indexes for better performance
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['payment_type', 'payment_status']);
            $table->index(['cheque_status', 'cheque_valid_date']);
            $table->index(['customer_id', 'payment_date']);
            $table->index(['recovery_for_payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['payment_type', 'payment_status']);
            $table->dropIndex(['cheque_status', 'cheque_valid_date']);
            $table->dropIndex(['customer_id', 'payment_date']);
            $table->dropIndex(['recovery_for_payment_id']);
            
            // Drop columns
            $table->dropForeign(['recovery_for_payment_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'recovery_for_payment_id',
                'bank_account_number', 
                'card_type',
                'actual_payment_method',
                'created_by',
                'updated_by'
            ]);
        });
    }
};