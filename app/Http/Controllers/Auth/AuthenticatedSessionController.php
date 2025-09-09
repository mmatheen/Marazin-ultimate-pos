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
        // Validate input
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Detect if email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';

        // Check if it's an API request
        $isApi = $request->expectsJson() || str_starts_with($request->path(), 'api/');

        // Find user by field
        $user = User::where($field, $login)->first();

        // Validate credentials
        if (!$user || !Hash::check($password, $user->password)) {
            if ($isApi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials.'
                ], 401);
            }

            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.']
            ]);
        }

        // ✅ Web Login: Authenticate session
        if (!$isApi) {
            Auth::login($user, $request->boolean('remember'));

            // Store default location in session
            if ($user->role_name) {
                $selectedLocation = Location::first();
                if ($selectedLocation) {
                    session()->put('selectedLocation', $selectedLocation->id);
                }
            }

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
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                'email' => $user->email,
                'role_name' => $user->getRoleNames()->first() ?? null,
                'locations' => $user->locations ? $user->locations->map(function ($loc) {
                    return [
                        'id' => $loc->id,
                        'name' => $loc->name,
                        'code' => $loc->code ?? null,
                    ];
                }) : [],
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