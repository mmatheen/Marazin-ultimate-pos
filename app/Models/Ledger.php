<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = [
        'ledger_type', 'reference_id', 'reference_type', 'transaction_type', 'amount', 'paid', 'due', 'running_due_balance'
    ];

    public function reference()
    {
        return $this->morphTo(); // Polymorphic relation to either customer or supplier
    }
}
