<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function purchaseReturn(){
        return view('purchase.purchase_return');
    }

    public function addPurchaseReturn(){
        return view('purchase.add_purchase_return');
    }
}
