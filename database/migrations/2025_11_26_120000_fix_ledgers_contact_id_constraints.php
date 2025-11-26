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
        // Step 1: Handle existing contact_id column - copy data from user_id if needed
        if (Schema::hasColumn('ledgers', 'user_id') && Schema::hasColumn('ledgers', 'contact_id')) {
            // Copy data from user_id to contact_id where contact_id is null
            DB::statement('UPDATE ledgers SET contact_id = user_id WHERE contact_id IS NULL AND user_id IS NOT NULL');
        }
        
        // Step 2: Set any remaining NULL contact_id to 0 (system/unknown contact)
        DB::statement('UPDATE ledgers SET contact_id = 0 WHERE contact_id IS NULL');
        
        // Step 3: Now safely make contact_id NOT NULL (only if column exists)
        if (Schema::hasColumn('ledgers', 'contact_id')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->bigInteger('contact_id')->unsigned()->nullable(false)->change();
            });
        }
        
        // Step 4: Add remaining columns if they don't exist
        Schema::table('ledgers', function (Blueprint $table) {
            // Add notes column first if not exists
            if (!Schema::hasColumn('ledgers', 'notes')) {
                $table->text('notes')->nullable()->after('credit');
            }
            
            // Add status column for tracking active/reversed entries
            if (!Schema::hasColumn('ledgers', 'status')) {
                $table->enum('status', ['active', 'reversed'])->default('active')->after('credit');
            }
            
            // Add created_by for audit tracking (after notes since notes was referenced)
            if (!Schema::hasColumn('ledgers', 'created_by')) {
                if (Schema::hasColumn('ledgers', 'notes')) {
                    $table->bigInteger('created_by')->unsigned()->nullable()->after('notes');
                } else {
                    $table->bigInteger('created_by')->unsigned()->nullable()->after('credit');
                }
            }
        });
        
        // Step 4.1: Remove balance column in separate step to avoid conflicts
        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'balance')) {
                $table->dropColumn('balance');
            }
        });
        
        // Step 5: Drop the old user_id column (data is now safely in contact_id)
        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            // Add back user_id column
            if (!Schema::hasColumn('ledgers', 'user_id')) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
            }
        });
        
        // Copy data back from contact_id to user_id
        if (Schema::hasColumn('ledgers', 'contact_id') && Schema::hasColumn('ledgers', 'user_id')) {
            DB::statement('UPDATE ledgers SET user_id = contact_id WHERE contact_id != 0');
        }
        
        Schema::table('ledgers', function (Blueprint $table) {
            // Add back balance column
            if (!Schema::hasColumn('ledgers', 'balance')) {
                $table->decimal('balance', 10, 2)->default(0)->after('credit');
            }
            
            // Remove new columns
            if (Schema::hasColumn('ledgers', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('ledgers', 'created_by')) {
                $table->dropColumn('created_by');
            }
            
            // Make contact_id nullable again
            $table->bigInteger('contact_id')->unsigned()->nullable()->change();
        });
    }
};