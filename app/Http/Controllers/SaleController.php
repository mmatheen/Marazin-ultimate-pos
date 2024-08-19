<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;

class SaleController extends Controller
{
    public function listSale(){
        return view('sell.sale');
    }
    public function addSale(){
        return view('sell.add_sale');
    }
}
