<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\IssueEmailVerificationCode;
use App\Actions\Auth\VerifyEmailVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    /**
     * Verify the user's email address.
     */
    public function verify(EmailVerificationRequest $request, VerifyEmailVerificationCode $verifyAction): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        try {
            $verifyAction->execute($request->user(), $request->validated('code'));
        } catch (DomainException $e) {
            return back()->withErrors([
                'code' => __('auth.failed_otp'),
            ]);
        }

        return redirect()->route('home');
    }

    /**
     * Send a new email verification notification.
     */
    public function resend(Request $request, IssueEmailVerificationCode $issueAction): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $issueAction->execute($request->user());

        return back()->with('status', 'verification-link-sent');
    }
}
