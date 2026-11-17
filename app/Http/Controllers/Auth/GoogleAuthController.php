<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Credit\CreditService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function __construct(private readonly CreditService $credits) {}

    public function redirect(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->frontendRedirect('/login?google=not_configured', $request);
        }

        $state = Str::random(40);
        $request->session()->put([
            'google_oauth_state' => $state,
            'google_login_redirect' => $this->safeRedirectPath($request->query('redirect')),
        ]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->googleRedirectUri($request),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request)
    {
        $expectedState = $request->session()->pull('google_oauth_state');
        $receivedState = $request->query('state');

        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            Log::notice('Google sign-in rejected due to invalid OAuth state');

            return $this->frontendRedirect('/login?google=failed', $request);
        }

        if ($request->query('error') === 'access_denied') {
            return $this->frontendRedirect('/login?google=cancelled', $request);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return $this->frontendRedirect('/login?google=failed', $request);
        }

        try {
            $tokenResponse = Http::asForm()->acceptJson()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->googleRedirectUri($request),
                'grant_type' => 'authorization_code',
            ]);

            $accessToken = $tokenResponse->json('access_token');
            if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
                Log::warning('Google sign-in token exchange failed', ['status' => $tokenResponse->status()]);

                return $this->frontendRedirect('/login?google=failed', $request);
            }

            $profileResponse = Http::withToken($accessToken)->acceptJson()->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException $exception) {
            Log::warning('Google sign-in could not reach the OAuth service', ['reason' => class_basename($exception)]);

            return $this->frontendRedirect('/login?google=failed', $request);
        }

        $profile = $profileResponse->json();
        if (! $profileResponse->successful() || ! is_array($profile) || ! filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Google sign-in returned an invalid or unverified account', ['status' => $profileResponse->status()]);

            return $this->frontendRedirect('/login?google=unverified', $request);
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->frontendRedirect('/login?google=failed', $request);
        }

        $user = User::withTrashed()->where('email', $email)->first();
        if ($user?->trashed()) {
            return $this->frontendRedirect('/login?google=unavailable', $request);
        }

        if (! $user) {
            if (! Setting::get('registration_enabled', true)) {
                return $this->frontendRedirect('/login?google=registration_disabled', $request);
            }

            $user = DB::transaction(function () use ($profile, $email) {
                $name = trim((string) ($profile['name'] ?? ''));
                $user = User::create([
                    'name' => Str::limit($name !== '' ? $name : Str::before($email, '@'), 100, ''),
                    'email' => $email,
                    'password' => Str::random(64),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->assignRole('user');
                $this->credits->grantSignup($user);

                return $user->fresh();
            });
        }

        if (! $user->isActive()) {
            return $this->frontendRedirect('/login?google=unavailable', $request);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirectPath = $request->session()->pull('google_login_redirect', '/dashboard');

        return $this->frontendRedirect($this->safeRedirectPath($redirectPath), $request);
    }

    private function safeRedirectPath(mixed $path): string
    {
        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\r\n]/', $path)) {
            return '/dashboard';
        }

        return $path;
    }

    private function googleRedirectUri(Request $request): string
    {
        return config('nexmind.google_redirect_uri')
            ?: rtrim($request->getSchemeAndHttpHost(), '/').'/auth/google/callback';
    }

    private function frontendRedirect(string $path, Request $request)
    {
        $baseUrl = config('nexmind.frontend_url') ?: $request->getSchemeAndHttpHost();

        return redirect()->away(rtrim($baseUrl, '/').$path);
    }
}
