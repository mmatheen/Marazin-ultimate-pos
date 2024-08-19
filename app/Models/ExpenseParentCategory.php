<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseParentCategory extends Model
{
    use HasFactory;
    protected $table='expense_parent_categories';
    protected $fillable=[
              'expenseParentCatergoryName',
              'description',
    ];
}