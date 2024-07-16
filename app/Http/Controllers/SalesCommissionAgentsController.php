<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesCommissionAgentsController extends Controller
{
    public function SalesCommissionList(){
        return view('sales_commission.salescommissionlist');
    }
}
