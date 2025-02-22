<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ledger extends Model
{
    protected $fillable = [
        'transaction_date',
        'reference_no',
        'transaction_type',
        'debit',
        'credit',
        'balance',
        'contact_type',
        'user_id'
    ];

    // Calculate balance cumulatively for each transaction
    public static function calculateBalance($user_id, $contact_type)
    {
        // Get all ledgers for the given user and contact type, ordered by transaction date
        $ledgers = self::where('user_id', $user_id)
                        ->where('contact_type', $contact_type)
                        ->orderBy('transaction_date')
                        ->get();

        $previous_balance = 0;

        foreach ($ledgers as $ledger) {
            // Calculate the cumulative balance
            $debit = $ledger->debit;
            $credit = $ledger->credit;
            $balance = $previous_balance + $credit - $debit;

            // Update the balance in the ledger record
            $ledger->balance = $balance;
            $ledger->save();

            // Set the previous balance for the next iteration
            $previous_balance = $balance;
        }
    }
}
