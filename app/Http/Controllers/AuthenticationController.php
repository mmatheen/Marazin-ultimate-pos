<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public function dashboard()
    {
        return view('includes.dashboards.dashboard');
    }


    public function getDetailsFromGuardDetailsUsingLoginUer()
    {
        // Retrieve the authenticated user's details, including their location if it exists
        $user = auth()->guard('web')->user();
        // Check if the user exists and has an associated location
        if ($user && $user->location) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'user' => [
                        'name' => $user->name,
                    ],
                    'location' => [
                        'name' => $user->location->name,
                    ]
                ]
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

}
