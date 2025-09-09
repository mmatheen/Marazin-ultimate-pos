<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * Display the cities listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales_rep_module.cities.index');
    }
}
