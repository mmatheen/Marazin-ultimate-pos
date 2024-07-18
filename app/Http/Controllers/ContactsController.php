<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactsController extends Controller
{
    public function SupplierList(){
        return view('contacts.supplier_list');
    }
}
