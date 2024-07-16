<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CatergoriesController extends Controller
{
    public function CatergoriesList(){
        return view('catergory.list_catergories');
    }
}
