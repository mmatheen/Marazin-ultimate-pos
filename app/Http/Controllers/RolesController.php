<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function RoleList(){
        return view('roles.role_list');
    }
    public function AddRole(){
        return view('roles.add_roles');
    }
}
