<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CatergoryController extends Controller
{
    public function category(){
        return view('category.category');
    }
}
