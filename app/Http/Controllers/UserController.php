<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function user(){
        return view('user.user');
    }

    public function addUser(){
        return view('user.add_user');
    }
}
