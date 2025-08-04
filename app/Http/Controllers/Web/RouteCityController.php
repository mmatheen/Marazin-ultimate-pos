<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;


class RouteCityController extends Controller
{
    public function create()
    {
        return view('sales_rep_module.route_cities.index');
    }
}
