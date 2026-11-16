<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => ['These credentials do not match our records.']]);
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::guard('web')->logout();

            return response()->json(['message' => 'Your account has been suspended. Please contact support.'], 403);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return new UserResource($user->load('plan'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request)
    {
        return new UserResource($request->user()->load('plan'));
    }
}
