<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellingPriceController extends Controller
{
    public function SellingPriceList(){
        return view('sellingPriceGroup.list_sellingPriceGroup');
    }
}
