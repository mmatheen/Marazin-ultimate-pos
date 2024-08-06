<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;

class SaleController extends Controller
{
    public function listSale(){
        return view('sell.sale');
    }
}
