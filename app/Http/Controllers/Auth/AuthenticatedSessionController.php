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

        // Load user relationships for API response
        $user->load(['roles', 'locations']);
        
        $token = $user->createToken('mobile_token')->plainTextToken;
        
        // Get role information from Spatie roles
        $role = $user->roles->first();
        $roleName = $role?->name ?? null;
        $roleKey = $role?->key ?? null;

        return response()->json([
            'status' => 'success',
            'message' => "Welcome back, {$user->user_name}!" . ($roleName ? " You're logged in as {$roleName}." : ""),
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'full_name' => $user->full_name ?? null,
                'name_title' => $user->name_title ?? null,
                'email' => $user->email,
                'role' => $roleName,
                'role_key' => $roleKey,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'can_bypass_location_scope' => $role?->bypass_location_scope ?? false,
                'is_master_super_admin' => $roleName === 'Master Super Admin',
                'is_super_admin' => $roleKey === 'super_admin',
                'locations' => $user->locations->map(function ($loc) {
                    return [
                        'id' => $loc->id,
                        'name' => $loc->name,
                        'code' => $loc->code ?? null,
                    ];
                })->toArray(),
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