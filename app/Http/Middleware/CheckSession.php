<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class CheckSession
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            // Store the intended URL only if not already present
            if (!$request->session()->has('url.intended')) {
                $request->session()->put('url.intended', URL::full());
            }

            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }

        return $next($request);
    }
}
