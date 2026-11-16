<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        return response()->json(['data' => $this->analytics->userOverview($request->user()->load('plan'))]);
    }

    public function analytics(Request $request)
    {
        $days = (int) $request->query('days', 30);

        return response()->json(['data' => $this->analytics->dailyUsage($request->user(), $days)]);
    }
}
