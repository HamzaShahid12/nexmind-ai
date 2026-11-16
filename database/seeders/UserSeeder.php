<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Services\Credit\CreditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Passwords come from SEED_ADMIN_PASSWORD / SEED_DEMO_PASSWORD. If unset, a random one is
     * generated and printed ONCE to the console. Nothing is hardcoded.
     */
    public function run(): void
    {
        $credits = app(CreditService::class);
        $free = Plan::default();

        $accounts = [
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@nexmind.test'), 'name' => 'Super Admin', 'role' => 'super_admin', 'password' => env('SEED_ADMIN_PASSWORD')],
            ['email' => 'demo@nexmind.test', 'name' => 'Demo User', 'role' => 'user', 'password' => env('SEED_DEMO_PASSWORD')],
        ];

        foreach ($accounts as $account) {
            if (User::withTrashed()->where('email', $account['email'])->exists()) {
                continue;
            }

            $password = $account['password'] ?: Str::password(16, symbols: false);

            $user = User::create(['name' => $account['name'], 'email' => $account['email'], 'password' => $password]);
            $user->forceFill(['email_verified_at' => now(), 'plan_id' => $free?->id])->save();
            $user->assignRole($account['role']);
            $credits->grantSignup($user);

            if (! $account['password'] && $this->command) {
                $this->command->warn("Created {$account['email']} with generated password: {$password}  (shown once)");
            }
        }
    }
}
