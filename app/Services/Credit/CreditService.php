<?php

namespace App\Services\Credit;

use App\Models\AiGeneration;
use App\Models\CreditTransaction;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The ONLY place credit balances change. Every mutation runs in a transaction with a
 * row lock on the user so concurrent requests cannot overspend, and always writes a
 * credit_transactions row. Amounts always come from the server, never from the client.
 */
class CreditService
{
    public function assertCanAfford(User $user, int $cost): void
    {
        $balance = (int) User::query()->whereKey($user->id)->value('credits_balance');
        if ($balance < $cost) {
            throw new InsufficientCreditsException($cost, $balance);
        }
    }

    /** Charge for a successful generation. Must be called only after the AI call succeeded. */
    public function charge(User $user, int $amount, string $description, ?AiGeneration $generation = null): CreditTransaction
    {
        return $this->apply($user, -abs($amount), 'usage', $description, $generation?->id);
    }

    public function grantSignup(User $user): void
    {
        $plan = $user->plan ?? Plan::default();
        DB::transaction(function () use ($user, $plan) {
            $user->forceFill(['plan_id' => $plan?->id, 'credits_reset_at' => now()->addMonth()])->save();
            if ($plan && $plan->monthly_credits > 0) {
                $this->apply($user, $plan->monthly_credits, 'signup', "Welcome credits ({$plan->name} plan)");
            }
        });
    }

    /** Admin adjustment. Positive adds, negative deducts. A deduction can never push the balance below zero. */
    public function adjust(User $user, int $delta, string $reason, User $admin): CreditTransaction
    {
        return $this->apply($user, $delta, $delta >= 0 ? 'admin_add' : 'admin_deduct', $reason, null, $admin->id);
    }

    /** Monthly renewal: expire unused credits, then grant the plan allowance. Returns users renewed. */
    public function renewDue(): int
    {
        $count = 0;
        User::query()->where('status', 'active')->whereNotNull('credits_reset_at')->where('credits_reset_at', '<=', now())
            ->orderBy('id')->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    DB::transaction(function () use ($user) {
                        $fresh = User::query()->whereKey($user->id)->lockForUpdate()->first();
                        if ($fresh->credits_balance > 0) {
                            $this->apply($fresh, -$fresh->credits_balance, 'expiry', 'Unused credits expired at renewal');
                        }
                        $plan = $fresh->plan ?? Plan::default();
                        if ($plan && $plan->monthly_credits > 0) {
                            $this->apply($fresh, $plan->monthly_credits, 'renewal', "Monthly credits ({$plan->name} plan)");
                        }
                        $fresh->forceFill(['credits_reset_at' => now()->addMonth()])->save();
                    });
                    $count++;
                }
            });

        return $count;
    }

    private function apply(User $user, int $delta, string $type, string $description, ?int $generationId = null, ?int $adminId = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $delta, $type, $description, $generationId, $adminId) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $new = $locked->credits_balance + $delta;

            if ($new < 0) {
                throw new InsufficientCreditsException(abs($delta), $locked->credits_balance);
            }

            $locked->forceFill(['credits_balance' => $new])->save();
            $user->credits_balance = $new;

            return CreditTransaction::create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $delta,
                'balance_after' => $new,
                'description' => $description,
                'ai_generation_id' => $generationId,
                'admin_id' => $adminId,
            ]);
        });
    }
}
