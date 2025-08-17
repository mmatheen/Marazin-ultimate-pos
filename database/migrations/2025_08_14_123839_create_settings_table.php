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
    public function up()
        {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('app_name', 255);
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->timestamps();
            });

            // Insert default setting
            DB::table('settings')->insert([
                'app_name' => 'My App',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
