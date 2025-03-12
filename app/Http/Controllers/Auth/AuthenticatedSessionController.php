<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Location;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */

    // public function store(LoginRequest $request): RedirectResponse
    // {

    //     $request->authenticate();
    //     $request->session()->regenerate();
    //     if(Auth::user()->role_name){
    //         $selectedLocation = Location::first();
    //         if(!empty($selectedLocation)){
    //             session()->put('selectedLocation', $selectedLocation->id);
    //         }
    //     }
    //     return redirect()->intended(RouteServiceProvider::HOME)->with('toastr-success', "Welcome back system user");
    // }


    // it will take for login user_name or password
    public function store(LoginRequest $request): RedirectResponse
    {
        $login = $request->input('login'); // Get the login input (either user_name or email)

        // Check if the login is an email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name'; // Default to 'username' if not email

        // Attempt to authenticate based on either email or username
        if (Auth::attempt([$field => $login, 'password' => $request->password])) {
            $request->session()->regenerate(); // Regenerate session on successful login

            // Check user's role and handle the selected location
            if (Auth::user()->role_name) {
                $selectedLocation = Location::first();
                if (!empty($selectedLocation)) {
                    session()->put('selectedLocation', $selectedLocation->id);
                }
            }

            // Get the user's name for the success message
            $userName = Auth::user()->user_name;
            $roleName = Auth::user()->role_name;

            // Redirect to the intended page after successful login and show the success message with user name

                return redirect()->intended(RouteServiceProvider::HOME)
             ->with('toastr-success', "Welcome back, {$userName}! You're logged in as {$roleName}.");

        }

        // If authentication fails, return with an error message
        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ]);
    }


    /**
     * Destroy an authenticated session.
     */

    public function destroy(Request $request): RedirectResponse
    {
        $userName = Auth::user()->user_name;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('toastr-success', "Logout Successfully, $userName!");
    }
}
