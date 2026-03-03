<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select("SHOW TABLE STATUS WHERE Name = 'invoice_counters'");
echo "Engine: " . $result[0]->Engine . "\n";

$result2 = DB::select("SHOW TABLE STATUS WHERE Name = 'sales'");
echo "Sales Engine: " . $result2[0]->Engine . "\n";

// Check the transaction isolation level
$isolation = DB::select("SELECT @@transaction_isolation");
echo "Transaction Isolation: " . $isolation[0]->{'@@transaction_isolation'} . "\n";

// Check if the storeOrUpdate is inside a transaction and the generateInvoiceNo is nested
echo "\nChecking for nested transaction issue...\n";
echo "The storeOrUpdate wraps everything in DB::transaction()\n";
echo "Inside that, generateInvoiceNo() also uses DB::transaction()\n";
echo "In Laravel, nested DB::transaction() uses SAVEPOINTS, not a new transaction.\n";
echo "This means lockForUpdate() in the inner transaction shares the SAME connection/lock as the outer.\n";
echo "If two requests both enter the outer DB::transaction() simultaneously,\n";
echo "the lockForUpdate() in generateInvoiceNo SHOULD block one until the other commits.\n";
echo "BUT: the lock is only released when the OUTER transaction commits!\n";
