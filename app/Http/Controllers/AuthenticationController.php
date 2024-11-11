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


    public function getAlluserDetails()
    {
        $getValue = User::with('location')->get();
        $getValue = User::with('location')->get()->pluck('location')->unique('id')->values();
        if ($getValue->count() > 0) {

            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
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


    //it will update the location in backend session code start
    public function updateLocation(Request $request){
        session()->put('selectedLocation', $request->id);
        return response()->json([
            'status' => 200,
            'message' => "Location Changed Successfully"
        ]);
    }
    //it will update the location in backend session code end

}
