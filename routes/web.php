<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class);

// Serve the Vue SPA for every non-API URL (the client-side router handles the rest).
Route::get('/{any?}', fn () => view('app'))->where('any', '^(?!api|sanctum|storage|up).*$');
