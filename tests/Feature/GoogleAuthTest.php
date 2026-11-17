<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexmind.frontend_url' => 'http://localhost']);
        $this->seedBase();
    }

    public function test_google_sign_in_creates_a_verified_account_and_preserves_a_safe_redirect(): void
    {
        $this->configureGoogle();
        $state = $this->beginGoogleSignIn();

        $this->get(route('auth.google.callback', ['state' => $state, 'code' => 'google-code']))
            ->assertRedirect(config('nexmind.frontend_url').'/dashboard/settings');

        $user = User::where('email', 'google.user@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('user'));
        $this->assertSame(100, $user->credits_balance);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_sign_in_requires_a_valid_oauth_state(): void
    {
        $this->configureGoogle();
        Http::fake();

        $this->get(route('auth.google.callback', ['state' => 'wrong-state', 'code' => 'google-code']))
            ->assertRedirect(config('nexmind.frontend_url').'/login?google=failed');

        Http::assertNothingSent();
        $this->assertGuest('web');
    }

    public function test_google_sign_in_rejects_unverified_email_addresses(): void
    {
        $this->configureGoogle(['email_verified' => false]);
        $state = $this->beginGoogleSignIn();

        $this->get(route('auth.google.callback', ['state' => $state, 'code' => 'google-code']))
            ->assertRedirect(config('nexmind.frontend_url').'/login?google=unverified');

        $this->assertDatabaseMissing('users', ['email' => 'google.user@example.com']);
        $this->assertGuest('web');
    }

    public function test_google_sign_in_respects_disabled_registration(): void
    {
        Setting::put('registration_enabled', false);
        $this->configureGoogle();
        $state = $this->beginGoogleSignIn();

        $this->get(route('auth.google.callback', ['state' => $state, 'code' => 'google-code']))
            ->assertRedirect(config('nexmind.frontend_url').'/login?google=registration_disabled');

        $this->assertDatabaseMissing('users', ['email' => 'google.user@example.com']);
        $this->assertGuest('web');
    }

    public function test_google_sign_in_is_unavailable_without_client_credentials(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(config('nexmind.frontend_url').'/login?google=not_configured');
    }

    private function configureGoogle(array $profile = []): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response(array_merge([
                'email' => 'google.user@example.com',
                'name' => 'Google User',
                'email_verified' => true,
            ], $profile)),
        ]);
    }

    private function beginGoogleSignIn(): string
    {
        $response = $this->get(route('auth.google.redirect', ['redirect' => '/dashboard/settings']));
        $response->assertRedirect();

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        return $query['state'];
    }
}
