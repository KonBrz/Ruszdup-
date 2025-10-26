<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $request->session()->forget('url.intended');
            $redirectUrl = $request->user()->is_admin
                ? config('app.frontend_url').'/admin/dashboard?verified=1'
                : config('app.frontend_url').'/dashboard?verified=1';
            return redirect()->to($redirectUrl);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }



        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}
