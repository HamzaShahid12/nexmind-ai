<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /** Target of the signed link in the verification email. The `signed` middleware guarantees integrity. */
    public function verify(string $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(rtrim(config('nexmind.frontend_url'), '/').'/dashboard?verified=1');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }
}
