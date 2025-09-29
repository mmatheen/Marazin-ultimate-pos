<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseSubCategory extends Model
{
    use HasFactory,LocationTrait;
    protected $table='expense_sub_categories';
    protected $fillable=[
              'subExpenseCategoryname',
              'main_expense_category_id',
              'subExpenseCategoryCode',
              'description',
    ];

    public function mainExpenseCategory()
    {
        return $this->belongsTo(ExpenseParentCategory::class, 'main_expense_category_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_sub_category_id');
    }
}
