<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrintLabelController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:print product-labels', ['only' => ['printLabel']]);
        $this->middleware('permission:print barcodes', ['only' => ['printBarcodes']]);
        $this->middleware('permission:design labels', ['only' => ['designLabels']]);
        $this->middleware('permission:batch print labels', ['only' => ['batchPrint']]);
    }

    public function printLabel(){
        return view('print_label.print_label');
    }
}
