<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VariationController extends Controller
{
    public function variation(){
        return view('variation.variation');
    }
}
