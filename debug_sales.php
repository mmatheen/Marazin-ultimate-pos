<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
$start = Carbon::today()->startOfDay();
$end = Carbon::today()->endOfDay();
echo "Date range: $start to $end\n";
$c = DB::table('sales')->where('status','final')->where('transaction_type','invoice')->whereBetween('sales_date',[$start,$end])->count();
echo "Today final invoices (by sales_date): $c\n";
$c2 = DB::table('sales')->where('status','final')->where('transaction_type','invoice')->whereBetween('created_at',[$start,$end])->count();
echo "Today final invoices (by created_at): $c2\n";
$latest = DB::table('sales')->where('status','final')->where('transaction_type','invoice')->orderBy('id','desc')->limit(5)->get(['id','invoice_no','sales_date','created_at','location_id']);
echo "Latest 5:\n";
foreach($latest as $r){ echo "  ID={$r->id} inv={$r->invoice_no} date={$r->sales_date} loc={$r->location_id}\n"; }
$allToday = DB::table('sales')->whereBetween('sales_date',[$start,$end])->count();
echo "All records with today sales_date: $allToday\n";
