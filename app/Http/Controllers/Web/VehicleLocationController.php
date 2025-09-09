<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleLocationController extends Controller
{
      public function create() {
        return view('sales_rep_module.vehicle_locations.index');
    }  
}
