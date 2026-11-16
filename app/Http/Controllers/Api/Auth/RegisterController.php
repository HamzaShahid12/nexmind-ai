<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use App\Services\Credit\CreditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterController extends Controller
{
    public function __construct(private readonly CreditService $credits) {}

    public function __invoke(RegisterRequest $request)
    {
        if (! Setting::get('registration_enabled', true)) {
            return response()->json(['message' => 'Registration is currently closed.'], 403);
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());   // privileged columns are not mass-assignable
            $user->assignRole('user');
            $this->credits->grantSignup($user);

            return $user;
        });

        try {
            event(new Registered($user));                   // sends the verification email
        } catch (Throwable $e) {
            Log::warning('Verification email could not be sent', ['user_id' => $user->id, 'reason' => class_basename($e)]);
        }

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return (new UserResource($user->fresh('plan')))->response()->setStatusCode(201);
    }
}
