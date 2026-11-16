<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the account exists (prevents account enumeration).
        return response()->json(['message' => 'If an account exists for that email, a reset link has been sent.']);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data + ['password_confirmation' => $request->input('password_confirmation')], function ($user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => ['This reset link is invalid or has expired.']]);
        }

        return response()->json(['message' => 'Your password has been reset.']);
    }
}
