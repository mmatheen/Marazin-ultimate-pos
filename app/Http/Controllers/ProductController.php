<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\MainCategory;
use App\Models\SubCategory;

class ProductController extends Controller
{

    public function product(){
        return view('product.product');
    }

    public function addProduct(){
        $MainCategories = MainCategory::all(); // this course come from modal
        $SubCategories = SubCategory::with('mainCategory')->get(); // this course come from modal
        $brands=Brand::all();
        return view('product.add_product', compact('brands','SubCategories','MainCategories'));
    }


   public function updatePrice(){
    return view('product.update_price');
   }

   public function importProduct(){
    return view('product.import_product');
   }
}
