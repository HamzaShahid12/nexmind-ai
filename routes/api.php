<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AI\ChatController;
use App\Http\Controllers\Api\AI\DocumentController;
use App\Http\Controllers\Api\AI\GenerateController;
use App\Http\Controllers\Api\AI\ToolCatalogController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\User\ConversationController;
use App\Http\Controllers\Api\User\CreditController;
use App\Http\Controllers\Api\User\DashboardController;
use App\Http\Controllers\Api\User\HistoryController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\SavedContentController;
use Illuminate\Support\Facades\Route;

// Served under /api (Laravel default) => final URLs are /api/v1/...
Route::prefix('v1')->group(function () {

    // ---------- Public ----------
    Route::get('plans', [PublicController::class, 'plans']);
    Route::get('faqs', [PublicController::class, 'faqs']);
    Route::get('blog/categories', [PublicController::class, 'blogCategories']);
    Route::get('blog/posts', [PublicController::class, 'blogPosts']);
    Route::get('blog/posts/{slug}', [PublicController::class, 'blogPost']);
    Route::post('contact', [PublicController::class, 'contact'])->middleware('throttle:5,1');

    // ---------- Authentication ----------
    Route::post('register', RegisterController::class)->middleware('throttle:10,1');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    // Named route used by Laravel's verification email. The signature protects the link.
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    // ---------- Authenticated user ----------
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::get('user', [LoginController::class, 'user']);
        Route::post('email/verification-notification', [VerifyEmailController::class, 'resend'])->middleware('throttle:6,1');

        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/analytics', [DashboardController::class, 'analytics']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile', [ProfileController::class, 'update']);           // multipart avatar uploads (use _method=PUT)
        Route::put('profile/password', [ProfileController::class, 'password']);
        Route::post('profile/deletion-request', [ProfileController::class, 'requestDeletion']);

        Route::get('credits', [CreditController::class, 'index']);
        Route::get('credits/transactions', [CreditController::class, 'transactions']);

        Route::get('history', [HistoryController::class, 'index']);
        Route::get('history/{generation}', [HistoryController::class, 'show']);
        Route::delete('history/{generation}', [HistoryController::class, 'destroy']);

        Route::get('saved-content', [SavedContentController::class, 'index']);
        Route::post('saved-content', [SavedContentController::class, 'store']);
        Route::delete('saved-content/{savedContent}', [SavedContentController::class, 'destroy']);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::patch('conversations/{conversation}', [ConversationController::class, 'update']);
        Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy']);

        Route::get('documents', [DocumentController::class, 'index']);
        Route::delete('documents/{document}', [DocumentController::class, 'destroy']);

        // ---------- AI tools (rate limited) ----------
        Route::get('ai/tools', [ToolCatalogController::class, 'index']);
        Route::middleware('throttle:ai')->prefix('ai')->group(function () {
            Route::post('chat', [ChatController::class, 'store']);
            Route::post('content-writer', [GenerateController::class, 'store'])->defaults('tool', 'content-writer');
            Route::post('product-description', [GenerateController::class, 'store'])->defaults('tool', 'product-description');
            Route::post('social-media', [GenerateController::class, 'store'])->defaults('tool', 'social-media');
            Route::post('seo', [GenerateController::class, 'store'])->defaults('tool', 'seo-assistant');
            Route::post('email', [GenerateController::class, 'store'])->defaults('tool', 'email-generator');
            Route::post('document-summary', [DocumentController::class, 'store']);
            Route::post('document-summary/{document}/ask', [DocumentController::class, 'ask']);
        });

        // ---------- Admin (authorized on the server by the `admin` middleware) ----------
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('dashboard', Admin\DashboardController::class);

            Route::get('users', [Admin\UserController::class, 'index']);
            Route::get('users/{user}', [Admin\UserController::class, 'show']);
            Route::put('users/{user}', [Admin\UserController::class, 'update']);
            Route::delete('users/{user}', [Admin\UserController::class, 'destroy']);
            Route::post('users/{user}/suspend', [Admin\UserController::class, 'suspend']);
            Route::post('users/{user}/activate', [Admin\UserController::class, 'activate']);
            Route::put('users/{user}/roles', [Admin\UserController::class, 'syncRoles']);
            Route::post('users/{user}/credits', [Admin\UserController::class, 'adjustCredits']);

            Route::get('roles', [Admin\RoleController::class, 'index']);
            Route::put('roles/{role}', [Admin\RoleController::class, 'update']);

            Route::get('ai-tools', [Admin\ToolController::class, 'index']);
            Route::put('ai-tools/{tool}', [Admin\ToolController::class, 'update']);
            Route::get('ai-settings', [Admin\SettingController::class, 'aiSettings']);
            Route::put('ai-settings', [Admin\SettingController::class, 'updateAi']);
            Route::get('settings', [Admin\SettingController::class, 'general']);
            Route::put('settings', [Admin\SettingController::class, 'updateGeneral']);

            Route::get('plans', [Admin\PlanController::class, 'index']);
            Route::post('plans', [Admin\PlanController::class, 'store']);
            Route::put('plans/{plan}', [Admin\PlanController::class, 'update']);
            Route::delete('plans/{plan}', [Admin\PlanController::class, 'destroy']);

            Route::get('credits', [Admin\ReportController::class, 'credits']);
            Route::get('transactions', [Admin\ReportController::class, 'transactions']);
            Route::get('generations', [Admin\ReportController::class, 'generations']);
            Route::get('generations/{generation}', [Admin\ReportController::class, 'generation']);
            Route::get('usage', [Admin\ReportController::class, 'usage']);

            Route::get('blogs', [Admin\BlogController::class, 'index']);
            Route::post('blogs', [Admin\BlogController::class, 'store']);
            Route::get('blogs/{post}', [Admin\BlogController::class, 'show']);
            Route::match(['put', 'post'], 'blogs/{post}', [Admin\BlogController::class, 'update']);   // POST + _method=PUT for image uploads
            Route::delete('blogs/{post}', [Admin\BlogController::class, 'destroy']);
            Route::post('blogs/{post}/publish', [Admin\BlogController::class, 'publish']);
            Route::post('blogs/{post}/unpublish', [Admin\BlogController::class, 'unpublish']);

            Route::get('categories', [Admin\BlogController::class, 'categories']);
            Route::post('categories', [Admin\BlogController::class, 'storeCategory']);
            Route::put('categories/{category}', [Admin\BlogController::class, 'updateCategory']);
            Route::delete('categories/{category}', [Admin\BlogController::class, 'destroyCategory']);

            Route::get('faqs', [Admin\FaqController::class, 'index']);
            Route::post('faqs', [Admin\FaqController::class, 'store']);
            Route::post('faqs/reorder', [Admin\FaqController::class, 'reorder']);
            Route::put('faqs/{faq}', [Admin\FaqController::class, 'update']);
            Route::delete('faqs/{faq}', [Admin\FaqController::class, 'destroy']);

            Route::get('contact-messages', [Admin\ContactController::class, 'index']);
            Route::get('contact-messages/{message}', [Admin\ContactController::class, 'show']);
            Route::patch('contact-messages/{message}', [Admin\ContactController::class, 'update']);
            Route::post('contact-messages/{message}/reply', [Admin\ContactController::class, 'reply']);
            Route::delete('contact-messages/{message}', [Admin\ContactController::class, 'destroy']);
        });
    });
});
