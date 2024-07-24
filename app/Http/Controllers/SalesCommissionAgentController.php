<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesCommissionAgentController extends Controller
{
    public function salesCommission(){
        return view('sales_commission.sales_commission');
    }
}
