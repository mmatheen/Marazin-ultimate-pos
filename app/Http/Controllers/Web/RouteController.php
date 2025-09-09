<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Display the routes listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales_rep_module.routes.index');
    }
}
