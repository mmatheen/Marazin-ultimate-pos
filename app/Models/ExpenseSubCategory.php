<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSubCategory extends Model
{
    use HasFactory;
    protected $table='expense_sub_categories';
    protected $fillable=[
              'subExpenseCategoryname',
              'main_expense_category_id',
              'subExpenseCategoryCode',
              'description',
    ];

    public function mainExpenseCategory()
    {
        return $this->belongsTo(ExpenseParentCategory::class); // Expense Parent Category is Expense Parent modal name
    }
}
