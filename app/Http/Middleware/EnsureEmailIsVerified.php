<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && is_null($user->email_verified_at)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your email address is not verified.'], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
