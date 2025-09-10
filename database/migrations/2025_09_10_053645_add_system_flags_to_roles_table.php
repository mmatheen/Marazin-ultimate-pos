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
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system_role')->default(false)->after('guard_name');
            $table->boolean('is_master_role')->default(false)->after('is_system_role');
            $table->boolean('bypass_location_scope')->default(false)->after('is_master_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['is_system_role', 'is_master_role', 'bypass_location_scope']);
        });
    }
};
