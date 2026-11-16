<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsService $analytics)
    {
        return response()->json(['data' => $analytics->adminOverview()]);
    }
}
