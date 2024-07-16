<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use function PHPUnit\Framework\returnSelf;

class BrandController extends Controller
{
    public function BrandList(){
        return view('brand.list_brands');
    }
}
