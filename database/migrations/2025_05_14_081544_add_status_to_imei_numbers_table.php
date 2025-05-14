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
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->enum('status', ['available', 'unavailable', 'reserved'])->default('available')->after('imei_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imei_numbers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
