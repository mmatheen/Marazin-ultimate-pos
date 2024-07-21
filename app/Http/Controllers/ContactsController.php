<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactsController extends Controller
{
    public function ImportContacts(){
        return view('contacts.import_contacts');
    }
}
