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
        Schema::create('expense_sub_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subExpenseCategoryname');
            $table->integer('main_expense_category_id')->unsigned();
            $table->string('subExpenseCategoryCode')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

              // ForeignKey
              $table->foreign('main_expense_category_id')->references('id')->on('expense_parent_categories');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_sub_categories');
    }
};
