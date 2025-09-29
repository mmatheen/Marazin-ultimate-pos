<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseParentCategory extends Model
{
    use HasFactory,LocationTrait;
    protected $table='expense_parent_categories';
    protected $fillable=[
              'expenseParentCatergoryName',
              'description',
    ];

    // Relationships
    public function expenseSubCategories()
    {
        return $this->hasMany(ExpenseSubCategory::class, 'main_expense_category_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_parent_category_id');
    }
}
