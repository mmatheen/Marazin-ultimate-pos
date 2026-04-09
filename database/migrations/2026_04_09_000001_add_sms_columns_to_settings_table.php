<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sms_user_id')->nullable()->after('favicon');
            $table->text('sms_api_key')->nullable()->after('sms_user_id');
            $table->string('sms_sender_id')->nullable()->after('sms_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_user_id',
                'sms_api_key',
                'sms_sender_id',
            ]);
        });
    }
};
