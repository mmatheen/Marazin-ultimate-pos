<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function importContact(){
        return view('contact.import_contact');
    }
}
