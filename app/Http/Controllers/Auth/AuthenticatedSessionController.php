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
     * Show login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Login (web + API).
     */
    public function store(LoginRequest $request)
    {
        $login = $request->input('login'); // email or username
        $password = $request->input('password');

        // Decide whether login is email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';

        if (Auth::attempt([$field => $login, 'password' => $password], $request->boolean('remember'))) {
            $user = Auth::user();

            // Store default location in session (web)
            if ($user->role_name) {
                $selectedLocation = Location::first();
                if (!empty($selectedLocation)) {
                    session()->put('selectedLocation', $selectedLocation->id);
                }
            }

            // API login (Sanctum token)
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                $token = $user->createToken('mobile_token')->plainTextToken;
                return response()->json([
                    'status' => 'success',
                    'message' => "Welcome back, {$user->user_name}! You're logged in as {$user->role_name}.",
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'user_name' => $user->user_name,
                        'role_name' => $user->getRoleNames()->first(),
                        'email' => $user->email
                    ]
                ]);
            }

            // Web login
            session()->regenerate();
            return redirect()->intended(RouteServiceProvider::HOME)
                ->with('toastr-success', "Welcome back, {$user->user_name}! You're logged in as {$user->role_name}.");
        }

        // Failed login
        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided credentials do not match our records.'
            ], 401);
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Logout (web + API).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userName = Auth::user()?->user_name ?? 'User';

        // Web logout
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('toastr-success', "Logout Successfully, $userName!");
    }
}
