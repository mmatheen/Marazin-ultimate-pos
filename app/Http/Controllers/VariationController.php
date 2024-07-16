<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VariationController extends Controller
{
    public function variatiuonList(){
        return view('variation.variation_list');
    }
}
