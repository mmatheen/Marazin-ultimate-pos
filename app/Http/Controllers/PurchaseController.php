<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function PurchaseList(){
        return view('purchase.purchase_lists');
    }
}
