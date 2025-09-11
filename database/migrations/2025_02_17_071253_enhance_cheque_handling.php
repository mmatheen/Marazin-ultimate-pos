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
        // Enhance payments table with cheque status tracking
        Schema::table('payments', function (Blueprint $table) {
            // Add cheque status tracking columns
            if (!Schema::hasColumn('payments', 'cheque_status')) {
                $table->enum('cheque_status', ['pending', 'deposited', 'cleared', 'bounced', 'cancelled'])->default('pending')->after('cheque_given_by');
            }
            if (!Schema::hasColumn('payments', 'cheque_clearance_date')) {
                $table->date('cheque_clearance_date')->nullable()->after('cheque_status');
            }
            if (!Schema::hasColumn('payments', 'cheque_bounce_date')) {
                $table->date('cheque_bounce_date')->nullable()->after('cheque_clearance_date');
            }
            if (!Schema::hasColumn('payments', 'cheque_bounce_reason')) {
                $table->text('cheque_bounce_reason')->nullable()->after('cheque_bounce_date');
            }
            if (!Schema::hasColumn('payments', 'bank_charges')) {
                $table->decimal('bank_charges', 10, 2)->default(0.00)->after('cheque_bounce_reason');
            }
            if (!Schema::hasColumn('payments', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed')->after('bank_charges');
            }
        });

        // Enhance sales table with payment tracking
        Schema::table('sales', function (Blueprint $table) {
            // Check if columns don't already exist
            if (!Schema::hasColumn('sales', 'final_total')) {
                $table->decimal('final_total', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('sales', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('invoice_no');
            }
        });

        // Create cheque status history table
        if (!Schema::hasTable('cheque_status_history')) {
            Schema::create('cheque_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
                $table->string('old_status')->nullable();
                $table->string('new_status');
                $table->date('status_date');
                $table->text('remarks')->nullable();
                $table->decimal('bank_charges', 10, 2)->default(0.00);
                $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['payment_id', 'status_date']);
            });
        }

        // Create cheque reminders table
        if (!Schema::hasTable('cheque_reminders')) {
            Schema::create('cheque_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
                $table->enum('reminder_type', ['due_soon', 'overdue', 'follow_up']);
                $table->date('reminder_date');
                $table->boolean('is_sent')->default(false);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                
                $table->index(['reminder_date', 'is_sent']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'cheque_status', 'cheque_clearance_date', 'cheque_bounce_date', 
                'cheque_bounce_reason', 'bank_charges', 'payment_status'
            ]);
        });

        Schema::dropIfExists('cheque_reminders');
        Schema::dropIfExists('cheque_status_history');
    }
};
