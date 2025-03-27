<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Sale;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set a default user_id for existing sales
        $defaultUser = User::first();
        $defaultUserId = $defaultUser ? $defaultUser->id : null;

        Schema::table('sales', function (Blueprint $table) use ($defaultUserId) {
            $table->foreignId('user_id')->nullable()->after('location_id')->constrained('users')->onDelete('cascade');
        });

        // Update existing sales to have the default user_id
        Sale::whereNull('user_id')->update(['user_id' => $defaultUserId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};