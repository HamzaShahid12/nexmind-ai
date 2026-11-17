<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class);
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Serve the Vue SPA for every non-API URL (the client-side router handles the rest).
Route::get('/{any?}', fn () => view('app'))->where('any', '^(?!api|sanctum|storage|up).*$');
