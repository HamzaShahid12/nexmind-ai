<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Plans are configurable from the admin panel. Only the Free plan can be used today:
     * there is no checkout or payment gateway, so Pro and Business are placeholders.
     * Credit allowances below are starting values, not commitments.
     */
    public function run(): void
    {
        $plans = [
            ['slug' => 'free', 'name' => 'Free', 'monthly_credits' => 100, 'is_default' => true, 'sort_order' => 1,
                'description' => 'Get started with monthly AI credits.',
                'features' => ['Limited monthly AI credits', 'Access to selected AI tools', 'Basic generation history', 'Standard output']],
            ['slug' => 'pro', 'name' => 'Pro', 'monthly_credits' => 1000, 'is_default' => false, 'sort_order' => 2,
                'description' => 'For regular users. Not yet available for purchase.',
                'features' => ['Higher monthly credit allowance', 'Access to all standard AI tools', 'Extended generation history', 'Priority processing where supported']],
            ['slug' => 'business', 'name' => 'Business', 'monthly_credits' => 5000, 'is_default' => false, 'sort_order' => 3,
                'description' => 'For teams and agencies. Not yet available for purchase.',
                'features' => ['Higher usage allowance', 'Business-focused AI tools', 'Team functionality (future)', 'Advanced usage reports']],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true, 'price' => 0]);
        }
    }
}
