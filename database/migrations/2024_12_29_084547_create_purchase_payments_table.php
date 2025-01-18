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
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('payment_method');
            $table->string('payment_account')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('payment_note')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['total_paid', 'total_due']);
        });
    }
};
