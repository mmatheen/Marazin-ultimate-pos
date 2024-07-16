<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function warrantyList(){
        return view('products.product_list');
    }

    public function product(){
        return view('products.add_product');
    }

   public function updatePrice(){
    return view('products.update_price');
   }

   public function importProduct(){
    return view('products.importProduct');
   }
}
