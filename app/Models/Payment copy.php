<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payable_type', 'payable_id', 'entity_id', 'entity_type', 'amount', 'due_amount', 'payment_method',
        'transaction_no', 'payment_date', 'cheque_number', 'cheque_date', 'bank_branch',
        'bank_account_number', 'card_number', 'card_holder_name', 'card_type',
        'expiry_month', 'expiry_year', 'security_code',
    ];

    // Polymorphic relation with sales, purchases, or returns
    public function payable()
    {
        return $this->morphTo();
    }

    // The customer or supplier related to this payment
    public function entity()
    {
        return $this->morphTo();
    }
}
