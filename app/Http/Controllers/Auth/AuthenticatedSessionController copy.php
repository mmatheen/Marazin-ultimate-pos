<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Location;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show login form.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle login for both web and API.
     */
    public function store(Request $request)
    {
        $login = $request->input('login');
        $password = $request->input('password');

        // Determine if login is email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';

        // Check if it's an API request
        $isApi = $request->expectsJson() || str_starts_with($request->path(), 'api/');

        // Find user
        $user = User::where($field, $login)->first();

        // Validate credentials manually for better API control
        if (!$user || !Hash::check($password, $user->password)) {
            if ($isApi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The provided credentials do not match our records.'
                ], 401);
            }

            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.'
            ]);
        }

        // ✅ Log in via web guard (for web sessions)
        if (!$isApi) {
            Auth::login($user, $request->boolean('remember'));

            // Store default location in session
            if ($user->role_name) {
                $selectedLocation = Location::first();
                if ($selectedLocation) {
                    session()->put('selectedLocation', $selectedLocation->id);
                }
            }

          // Web login
            session()->regenerate();
            return redirect()->intended(RouteServiceProvider::HOME)
                ->with('toastr-success', "Welcome back, {$user->user_name}! You're logged in as {$user->role_name}.");
        }

        // 🔐 API Login: Generate Sanctum Token
        $token = $user->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => "Welcome back, {$user->user_name}! You're logged in as {$user->role_name}.",
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'role_name' => $user->getRoleNames()->first() ?? null,
                'email' => $user->email,
                'locations' => $user->locations ?? []
            ]
        ], 200);
    }

    /**
     * Logout (web + API).
     */
    public function destroy(Request $request)
    {
        $userName = Auth::user()?->user_name ?? 'User';

        // Web logout
        if (!$request->expectsJson() && !str_starts_with($request->path(), 'api/')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->with('toastr-success', "Logout Successfully, $userName!");
        }

        // API logout: Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.'
        ], 200);
    }
}