<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellingPriceController extends Controller
{
    public function sellingPrice(){
        return view('selling_price_group.selling_price_group');
    }
}
