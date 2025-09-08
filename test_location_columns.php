<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Schema;

echo "Location table structure:" . PHP_EOL;
$columns = Schema::getColumnListing('locations');
print_r($columns);

echo PHP_EOL . "Checking if logo_image column exists: " . (in_array('logo_image', $columns) ? 'YES' : 'NO') . PHP_EOL;
