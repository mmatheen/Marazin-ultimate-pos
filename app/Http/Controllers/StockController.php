<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StockController extends Controller
{
    public function importOpeningStock(){
        return view('stock.import_opening_stock');
    }
}
