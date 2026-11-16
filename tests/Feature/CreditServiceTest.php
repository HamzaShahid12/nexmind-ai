<?php

namespace Tests\Feature;

use App\Services\Credit\CreditService;
use App\Services\Credit\InsufficientCreditsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    private CreditService $credits;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->credits = app(CreditService::class);
    }

    public function test_charge_reduces_balance_and_records_a_transaction(): void
    {
        $user = $this->makeUser('user', 10);

        $tx = $this->credits->charge($user, 3, 'Test');

        $this->assertSame(7, $user->fresh()->credits_balance);
        $this->assertSame(-3, $tx->amount);
        $this->assertSame(7, $tx->balance_after);
    }

    public function test_balance_can_never_go_negative(): void
    {
        $user = $this->makeUser('user', 2);

        $this->expectException(InsufficientCreditsException::class);
        try {
            $this->credits->charge($user, 5, 'Too expensive');
        } finally {
            $this->assertSame(2, $user->fresh()->credits_balance);
            $this->assertDatabaseCount('credit_transactions', 0);
        }
    }

    public function test_charging_exactly_the_balance_is_allowed(): void
    {
        $user = $this->makeUser('user', 5);
        $this->credits->charge($user, 5, 'All in');
        $this->assertSame(0, $user->fresh()->credits_balance);
    }

    public function test_signup_grants_plan_credits_and_schedules_renewal(): void
    {
        $user = $this->makeUser('user', 0);
        $user->forceFill(['credits_reset_at' => null])->save();

        $this->credits->grantSignup($user);

        $user->refresh();
        $this->assertSame(100, $user->credits_balance);
        $this->assertNotNull($user->credits_reset_at);
    }

    public function test_monthly_renewal_expires_unused_credits_and_grants_the_allowance(): void
    {
        $user = $this->makeUser('user', 30);
        $user->forceFill(['credits_reset_at' => now()->subHour()])->save();
        $notDue = $this->makeUser('user', 30);
        $notDue->forceFill(['credits_reset_at' => now()->addDays(5)])->save();

        $count = $this->credits->renewDue();

        $this->assertSame(1, $count);
        $this->assertSame(100, $user->fresh()->credits_balance);
        $this->assertSame(30, $notDue->fresh()->credits_balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'expiry', 'amount' => -30]);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'renewal', 'amount' => 100]);
        $this->assertTrue($user->fresh()->credits_reset_at->isFuture());
    }

    public function test_suspended_users_are_not_renewed(): void
    {
        $user = $this->makeUser('user', 30);
        $user->forceFill(['credits_reset_at' => now()->subHour(), 'status' => 'suspended'])->save();

        $this->assertSame(0, $this->credits->renewDue());
        $this->assertSame(30, $user->fresh()->credits_balance);
    }
}
