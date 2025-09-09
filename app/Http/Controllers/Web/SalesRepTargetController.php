<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesRepTargetController extends Controller
{
    /**
     * Display the sales rep targets listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales_rep_module.targets.index');
    }
}
