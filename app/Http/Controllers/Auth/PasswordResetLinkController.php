<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $normalizedEmail = strtolower(trim($request->email));
        $hashedEmail = hash_hmac('sha256', $normalizedEmail.'|'.$request->ip(), config('app.key'));
        $key = 'password-reset-link:'.$hashedEmail;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => __('passwords.throttled')]);
        }

        RateLimiter::hit($key, 60); // 1 minute decay

        // Determine if user exists and is active
        $user = User::where('email', $request->email)->first();

        // Always return a neutral response to prevent user enumeration
        $status = Password::RESET_LINK_SENT;

        if ($user && $user->is_active) {
            Password::broker()->sendResetLink(
                $request->only('email')
            );
        }

        return back()->with('status', __($status));
    }
}
