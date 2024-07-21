<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function CustomerGroupList(){
        return view('contacts.customer_group_list');
    }
}
