<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Type of purchase: regular purchase, free claim receipt, or standalone claim
            $table->enum('purchase_type', ['regular', 'free_claim', 'free_claim_standalone'])
                  ->default('regular')
                  ->after('purchasing_status');

            // If this is a free_claim receipt, links back to the original purchase that generated the claim
            $table->unsignedBigInteger('claim_reference_id')
                  ->nullable()
                  ->after('purchase_type');

            // Only set on purchases that have claim_free_quantity > 0 on any product line
            $table->enum('claim_status', ['pending', 'partial', 'fulfilled'])
                  ->nullable()
                  ->after('claim_reference_id');

            $table->foreign('claim_reference_id')
                  ->references('id')
                  ->on('purchases')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['claim_reference_id']);
            $table->dropColumn(['purchase_type', 'claim_reference_id', 'claim_status']);
        });
    }
};
