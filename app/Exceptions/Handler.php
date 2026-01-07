<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated users (session expired)
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session has expired. Please log in again.',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')
            ->with('error', 'Your session has expired. Please log in again.');
    }

    /**
     * Handle CSRF token mismatch (419 Page Expired)
     */
    public function render($request, Throwable $exception)
    {
        // Handle CSRF token mismatch
        if ($exception instanceof TokenMismatchException) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired. Please log in again.',
                    'redirect' => route('login')
                ], 419);
            }

            return redirect()->route('login')
                ->with('error', 'Your session has expired. Please log in again.');
        }

        return parent::render($request, $exception);
    }
}
