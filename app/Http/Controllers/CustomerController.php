<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function CustomerList(){
        return view('contacts.customer_list');
    }
}
