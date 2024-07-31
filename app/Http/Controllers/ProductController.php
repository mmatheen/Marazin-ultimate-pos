<?php

namespace App\Http\Controllers;

use App\Models\Brand;
class ProductController extends Controller
{

    public function product(){
        return view('product.product');
    }

    public function addProduct(){

        $brands=Brand::all();
        return view('product.add_product', compact('brands'));
    }


   public function updatePrice(){
    return view('product.update_price');
   }

   public function importProduct(){
    return view('product.import_product');
   }
}
