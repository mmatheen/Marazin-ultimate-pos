<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function listPurchase(){
        return view('purchase.list_purchase');
    }

    public function AddPurchase(){
        return view('purchase.add_purchase');
    }

}
