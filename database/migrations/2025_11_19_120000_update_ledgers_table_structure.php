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
        Schema::table('ledgers', function (Blueprint $table) {
            // Step 1: Add new contact_id column if user_id exists and contact_id doesn't exist
            if (Schema::hasColumn('ledgers', 'user_id') && !Schema::hasColumn('ledgers', 'contact_id')) {
                $table->bigInteger('contact_id')->unsigned()->nullable()->after('id');
            }
        });
        
        // Step 2: Copy all data from user_id to contact_id
        if (Schema::hasColumn('ledgers', 'user_id') && Schema::hasColumn('ledgers', 'contact_id')) {
            DB::statement('UPDATE ledgers SET contact_id = user_id');
        }
        
        // Step 3: Make contact_id NOT NULL since it has data now
        if (Schema::hasColumn('ledgers', 'contact_id')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->bigInteger('contact_id')->unsigned()->nullable(false)->change();
            });
        }
        
        Schema::table('ledgers', function (Blueprint $table) {
            // Step 4: Remove balance column as it will be calculated dynamically
            if (Schema::hasColumn('ledgers', 'balance')) {
                $table->dropColumn('balance');
            }
            
            // Step 5: Add status column for tracking active/reversed entries
            if (!Schema::hasColumn('ledgers', 'status')) {
                $table->enum('status', ['active', 'reversed'])->default('active')->after('credit');
            }
            
            // Step 6: Add created_by for audit tracking
            if (!Schema::hasColumn('ledgers', 'created_by')) {
                $table->bigInteger('created_by')->unsigned()->nullable()->after('notes');
            }
            
            // Step 7: Add notes column if not exists
            if (!Schema::hasColumn('ledgers', 'notes')) {
                $table->text('notes')->nullable()->after('credit');
            }
            
            // Step 8: Drop the old user_id column (data is now safely in contact_id)
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
            // Step 1: Add back user_id column
            if (Schema::hasColumn('ledgers', 'contact_id') && !Schema::hasColumn('ledgers', 'user_id')) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
            }
        });
        
        // Step 2: Copy data back from contact_id to user_id
        if (Schema::hasColumn('ledgers', 'contact_id') && Schema::hasColumn('ledgers', 'user_id')) {
            DB::statement('UPDATE ledgers SET user_id = contact_id');
        }
        
        // Step 3: Make user_id NOT NULL
        if (Schema::hasColumn('ledgers', 'user_id')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
            });
        }
        
        Schema::table('ledgers', function (Blueprint $table) {
            // Step 4: Add back balance column and drop new columns
            if (!Schema::hasColumn('ledgers', 'balance')) {
                $table->decimal('balance', 10, 2)->default(0)->after('credit');
            }
            
            if (Schema::hasColumn('ledgers', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('ledgers', 'created_by')) {
                $table->dropColumn('created_by');
            }
            
            // Step 5: Drop contact_id column (data is now safely back in user_id)
            if (Schema::hasColumn('ledgers', 'contact_id')) {
                $table->dropColumn('contact_id');
            }
        });
    }
};
