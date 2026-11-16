<?php

namespace App\Providers;

use App\Models\AiGeneration;
use App\Models\Conversation;
use App\Models\SavedContent;
use App\Models\UploadedDocument;
use App\Policies\OwnedPolicy;
use App\Services\AI\AIManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class);
    }

    public function boot(): void
    {
        foreach ([AiGeneration::class, Conversation::class, SavedContent::class, UploadedDocument::class] as $model) {
            Gate::policy($model, OwnedPolicy::class);
        }

        // Password reset links open the Vue SPA, not a Laravel view.
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('nexmind.frontend_url'), '/')
            .'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }
}
