<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function UserList(){
        return view('User.userlist');
    }

    public function AddUser(){
        return view('User.add_user');
    }
}
