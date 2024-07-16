<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function UnitList(){
        return view('unit.list_unit');
    }
}
