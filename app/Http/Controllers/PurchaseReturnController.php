<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function PurchaseReturnList(){
        return view('purchase.purchase_return_list');
    }

    public function AddPurchaseReturn(){
        return view('purchase.add_purchase_return');
    }
}
