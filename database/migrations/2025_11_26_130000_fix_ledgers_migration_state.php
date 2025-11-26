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
        // This migration fixes the state when contact_id already exists but migration wasn't recorded
        
        // If contact_id already exists and has data, ensure it's properly configured
        if (Schema::hasColumn('ledgers', 'contact_id')) {
            // Copy data from user_id if contact_id is empty
            if (Schema::hasColumn('ledgers', 'user_id')) {
                DB::statement('UPDATE ledgers SET contact_id = user_id WHERE contact_id IS NULL AND user_id IS NOT NULL');
            }
            
            // Set default value for any remaining nulls
            DB::statement('UPDATE ledgers SET contact_id = 0 WHERE contact_id IS NULL');
            
            // Make contact_id NOT NULL
            Schema::table('ledgers', function (Blueprint $table) {
                $table->bigInteger('contact_id')->unsigned()->nullable(false)->change();
            });
        }
        
        // Add other missing columns if they don't exist
        Schema::table('ledgers', function (Blueprint $table) {
            if (!Schema::hasColumn('ledgers', 'status')) {
                $table->enum('status', ['active', 'reversed'])->default('active')->after('credit');
            }
            
            if (!Schema::hasColumn('ledgers', 'created_by')) {
                $table->bigInteger('created_by')->unsigned()->nullable()->after('notes');
            }
            
            // Add notes column if not exists (might be from an earlier migration)
            if (!Schema::hasColumn('ledgers', 'notes')) {
                $table->text('notes')->nullable()->after('credit');
            }
        });
        
        // Remove balance column if it exists
        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'balance')) {
                $table->dropColumn('balance');
            }
        });
        
        // Drop user_id column if contact_id exists and has data
        if (Schema::hasColumn('ledgers', 'contact_id') && Schema::hasColumn('ledgers', 'user_id')) {
            $hasContactData = DB::table('ledgers')->whereNotNull('contact_id')->where('contact_id', '!=', 0)->exists();
            if ($hasContactData) {
                Schema::table('ledgers', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back user_id if it doesn't exist
        if (!Schema::hasColumn('ledgers', 'user_id') && Schema::hasColumn('ledgers', 'contact_id')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
            });
            
            // Copy data back
            DB::statement('UPDATE ledgers SET user_id = contact_id WHERE contact_id != 0');
        }
        
        // Add back balance column
        if (!Schema::hasColumn('ledgers', 'balance')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->decimal('balance', 10, 2)->default(0)->after('credit');
            });
        }
        
        // Remove the columns we added
        Schema::table('ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('ledgers', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('ledgers', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};