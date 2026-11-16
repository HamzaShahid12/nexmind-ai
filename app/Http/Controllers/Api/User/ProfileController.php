<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource($request->user()->load('plan'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'language', 'tone', 'notify_email']);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        $emailChanged = $user->isDirty('email');
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return new UserResource($user->load('plan'));
    }

    public function password(ChangePasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'Password updated.']);
    }

    /** Records a request only. An administrator reviews it; nothing is deleted automatically. */
    public function requestDeletion(Request $request)
    {
        $user = $request->user();
        $user->forceFill(['deletion_requested_at' => now()])->save();
        ActivityLogger::log('account.deletion_requested', $user);

        return response()->json(['message' => 'Your account deletion request has been recorded. An administrator will review it.']);
    }
}
