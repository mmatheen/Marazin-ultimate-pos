<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database connection setup
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'marazin_pos_db',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== CHECKING TABLE STRUCTURES ===\n\n";

// Check customers table
echo "CUSTOMERS TABLE COLUMNS:\n";
echo "========================\n";
$customerColumns = DB::select('DESCRIBE customers');
foreach ($customerColumns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\nSALES TABLE COLUMNS:\n";
echo "====================\n";
$salesColumns = DB::select('DESCRIBE sales');
foreach ($salesColumns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\nPAYMENTS TABLE COLUMNS:\n";
echo "=======================\n";
$paymentColumns = DB::select('DESCRIBE payments');
foreach ($paymentColumns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\nLEDGERS TABLE COLUMNS:\n";
echo "======================\n";
$ledgerColumns = DB::select('DESCRIBE ledgers');
foreach ($ledgerColumns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

?>