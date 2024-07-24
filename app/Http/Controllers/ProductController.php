<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function product(){
        return view('product.product');
    }

    public function addProduct(){
        return view('product.add_product');
    }


   public function updatePrice(){
    return view('product.update_price');
   }

   public function importProduct(){
    return view('product.import_product');
   }
}
