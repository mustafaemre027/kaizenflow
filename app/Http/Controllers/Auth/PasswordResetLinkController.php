<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $hashedEmail = md5($request->email);
        $key = 'password-reset-link:' . $hashedEmail . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response('Too Many Attempts.', 429);
        }
        
        RateLimiter::hit($key, 60); // 1 minute decay

        // Determine if user exists and is active
        $user = \App\Models\User::where('email', $request->email)->first();

        // Always return a neutral response to prevent user enumeration
        $status = Password::RESET_LINK_SENT;

        if ($user && $user->is_active) {
            Password::broker()->sendResetLink(
                $request->only('email')
            );
        }

        return redirect()->route('login')->with('status', __($status));
    }
}
