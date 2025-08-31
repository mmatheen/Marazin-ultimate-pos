<?php
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\Ledger;

// Initialize database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'marazin_pos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Testing Supplier 2 Ledger Data:\n\n";

// Get supplier 2
$supplier = Supplier::find(2);
if (!$supplier) {
    echo "Supplier 2 not found!\n";
    exit;
}

echo "Supplier: {$supplier->first_name} {$supplier->last_name}\n";
echo "Opening Balance: {$supplier->opening_balance}\n\n";

// Get purchases
$purchases = Purchase::where('supplier_id', 2)->get();
echo "Purchases:\n";
foreach ($purchases as $purchase) {
    echo "- {$purchase->reference_no}: Rs {$purchase->final_total} (Paid: Rs {$purchase->total_paid}, Due: Rs {$purchase->total_due})\n";
}
echo "\n";

// Get payments
$payments = Payment::where('supplier_id', 2)->get();
echo "Payments:\n";
foreach ($payments as $payment) {
    echo "- {$payment->reference_no}: Rs {$payment->amount} ({$payment->payment_method})\n";
}
echo "\n";

// Get ledger entries
$ledgers = Ledger::where('supplier_id', 2)->orderBy('created_at')->get();
echo "Ledger Entries:\n";
$balance = 0;
foreach ($ledgers as $ledger) {
    $balance = $balance + $ledger->debit - $ledger->credit;
    echo "- {$ledger->transaction_date}: {$ledger->transaction_type} | Debit: Rs {$ledger->debit} | Credit: Rs {$ledger->credit} | Balance: Rs {$balance}\n";
}

echo "\nFinal Balance: Rs {$balance}\n";
