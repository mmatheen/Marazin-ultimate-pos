<?php

namespace App\Http\Controllers;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;

class SaleController extends Controller
{
    public function listSale(){
        return view('sell.sale');
    }
    public function addSale(){
        return view('sell.add_sale');
    }
    public function pos()
        {
            $user = Auth::user();

            // Retrieve the user's associated location, or null if not assigned
            $location = Location::find($user->location_id);

            return view('sell.pos', compact('location'));
        }
    public function posList()
        {
            
            // $user = Auth::user();

            // // Retrieve the user's associated location, or null if not assigned
            // $location = Location::find($user->location_id);

            return view('sell.pos_list');
        }

}
