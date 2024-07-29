<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function stockTranfer(){
        return view('stock_tranfer.stock_transfer');
    }

    public function addStockTransfer(){
        return view('stock_tranfer.add_stock_transfer');
    }
}
