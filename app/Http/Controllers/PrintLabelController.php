<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrintLabelController extends Controller
{
    public function printLabel(){
        return view('printlabel.print_Label');
    }
}
