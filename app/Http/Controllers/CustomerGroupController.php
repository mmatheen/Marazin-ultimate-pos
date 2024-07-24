<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function customerGroup(){
        return view('contact.customer_group');
    }
}
