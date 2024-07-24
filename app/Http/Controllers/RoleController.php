<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function role(){
        return view('role.role');
    }
    public function addRole(){
        return view('role.add_role');
    }
}
