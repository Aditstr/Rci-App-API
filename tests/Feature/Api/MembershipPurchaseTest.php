<?php

namespace Tests\Feature\Api;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MembershipConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class MembershipPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-25 10:00:00');

        MembershipConfig::update([
            'price' => 20000,
            'duration_days' => 30,
            'ai_daily_limit' => 3,
            'enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function clientWithBalance(string $balance): User
    {
        $user = User::factory()->create(['role' => 'client']);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance]);

        return $user;
    }

    private function purchase(User $client, string $key, array $data = []): TestResponse
    {
        return $this->actingAs($client)->postJson(
            '/api/v1/rci/upgrade',
            $data,
            ['Idempotency-Key' => $key],
        );
    }

    public function test_purchase_uses_server_price_and_duration_not_request_values(): void
    {
        $client = $this->clientWithBalance('100000.00');

        $this->purchase($client, 'server-price', [
            'price' => 1,
            'duration_days' => 999,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.price', '20000.00');

        $subscription = Subscription::firstOrFail();

        $this->assertSame('20000.00', $subscription->price);
        $this->assertSame('2026-10-25 10:00:00', $subscription->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-24 10:00:00', $subscription->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('80000.00', $client->wallet->fresh()->balance);
    }

    public function test_price_and_duration_snapshot_survive_config_changes(): void
    {
        $client = $this->clientWithBalance('100000.00');
        $this->purchase($client, 'snapshot-1')->assertOk();

        $first = Subscription::firstOrFail();

        MembershipConfig::update([
            'price' => 35000,
            'duration_days' => 60,
            'ai_daily_limit' => 3,
            'enabled' => true,
        ]);

        $first->refresh();
        $this->assertSame('20000.00', $first->price);
        $this->assertSame('2026-11-24 10:00:00', $first->ends_at->format('Y-m-d H:i:s'));

        $this->purchase($client, 'snapshot-2')->assertOk();

        $second = Subscription::orderByDesc('id')->firstOrFail();
        $this->assertSame('35000.00', $second->price);
        $this->assertSame('2026-11-24 10:00:00', $second->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2027-01-23 10:00:00', $second->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_purchase_keeps_client_role_and_creates_no_escrow_hold(): void
    {
        $client = $this->clientWithBalance('50000.00');

        $this->purchase($client, 'role')->assertOk();

        $this->assertSame('client', $client->fresh()->role);
        $this->assertDatabaseMissing('wallet_transactions', ['type' => 'escrow_hold']);
        $this->assertSame(1, WalletTransaction::where('type', 'subscription_payment')->count());
    }

    public function test_wallet_debit_and_ledger_reference_the_new_subscription(): void
    {
        $client = $this->clientWithBalance('20000.00');

        $this->purchase($client, 'ledger')->assertOk();

        $subscription = Subscription::firstOrFail();
        $transaction = WalletTransaction::firstOrFail();

        $this->assertSame('subscription_payment', $transaction->type);
        $this->assertSame('20000.00', $transaction->amount);
        $this->assertSame('success', $transaction->status);
        $this->assertSame(Subscription::class, $transaction->reference_type);
        $this->assertSame($subscription->id, $transaction->reference_id);
        $this->assertSame('0.00', $client->wallet->fresh()->balance);
    }

    public function test_insufficient_balance_rolls_back_all_changes(): void
    {
        $client = $this->clientWithBalance('19999.00');

        $this->purchase($client, 'insufficient')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('19999.00', $client->wallet->fresh()->balance);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_disabled_membership_rejects_purchase(): void
    {
        MembershipConfig::update([
            'price' => 20000,
            'duration_days' => 30,
            'ai_daily_limit' => 3,
            'enabled' => false,
        ]);

        $client = $this->clientWithBalance('100000.00');

        $this->purchase($client, 'disabled')->assertStatus(422);

        $this->assertSame('100000.00', $client->wallet->fresh()->balance);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_renewal_extends_from_the_current_ends_at(): void
    {
        $client = $this->clientWithBalance('40000.00');

        $this->purchase($client, 'renewal-a')->assertOk();
        $this->purchase($client, 'renewal-b')->assertOk();

        $subscriptions = Subscription::orderBy('id')->get();

        $this->assertCount(2, $subscriptions);
        $this->assertSame(
            $subscriptions[0]->ends_at->toDateTimeString(),
            $subscriptions[1]->starts_at->toDateTimeString()
        );
        $this->assertSame('2026-12-24 10:00:00', $subscriptions[1]->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('0.00', $client->wallet->fresh()->balance);
        $this->assertSame(2, WalletTransaction::where('type', 'subscription_payment')->count());
    }

    public function test_expired_membership_starts_from_now(): void
    {
        $client = $this->clientWithBalance('20000.00');

        Subscription::create([
            'user_id' => $client->id,
            'plan_name' => 'pro',
            'price' => '20000.00',
            'currency' => 'IDR',
            'status' => 'active',
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ]);

        $this->purchase($client, 'expired')->assertOk();

        $new = Subscription::orderByDesc('id')->firstOrFail();
        $this->assertSame('2026-10-25 10:00:00', $new->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-24 10:00:00', $new->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_missing_wallet_is_rejected_without_side_effects(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->purchase($client, 'no-wallet')->assertStatus(422);

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_failure_after_debit_rolls_back_wallet_and_records(): void
    {
        $client = $this->clientWithBalance('50000.00');

        Subscription::creating(function (): void {
            throw new RuntimeException('Simulated failure.');
        });

        $this->purchase($client, 'failure')->assertStatus(422);

        $this->assertSame('50000.00', $client->wallet->fresh()->balance);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_repeated_purchase_without_balance_leaves_exactly_one_membership(): void
    {
        $client = $this->clientWithBalance('20000.00');

        $this->purchase($client, 'repeat-1')->assertOk();
        $this->purchase($client, 'repeat-2')->assertStatus(422);

        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertSame('0.00', $client->wallet->fresh()->balance);
    }

    public function test_legacy_upgrade_route_returns_subscription_details(): void
    {
        $client = $this->clientWithBalance('20000.00');

        $this->purchase($client, 'legacy')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['subscription_id', 'price', 'starts_at', 'ends_at'],
            ]);
    }

    public function test_same_idempotency_key_does_not_debit_or_subscribe_twice(): void
    {
        $client = $this->clientWithBalance('40000.00');

        $first = $this->purchase($client, 'purchase-1')->assertOk();

        $subscriptionId = $first->json('data.subscription_id');

        // A replay must still return the original result, even if membership
        // was disabled after the first purchase.
        MembershipConfig::update([
            'price' => 20000,
            'duration_days' => 30,
            'ai_daily_limit' => 3,
            'enabled' => false,
        ]);

        $this->purchase($client, 'purchase-1')
            ->assertOk()
            ->assertJsonPath('data.subscription_id', $subscriptionId);

        $this->assertSame('20000.00', $client->wallet->fresh()->balance);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertSame('purchase-1', Subscription::firstOrFail()->idempotency_key);
    }

    public function test_same_idempotency_key_is_scoped_per_user(): void
    {
        $firstClient = $this->clientWithBalance('20000.00');
        $secondClient = $this->clientWithBalance('20000.00');

        $first = $this->purchase($firstClient, 'shared-key')->assertOk();
        $second = $this->purchase($secondClient, 'shared-key')->assertOk();

        $this->assertNotSame(
            $first->json('data.subscription_id'),
            $second->json('data.subscription_id'),
        );

        $firstSubscription = Subscription::findOrFail($first->json('data.subscription_id'));
        $secondSubscription = Subscription::findOrFail($second->json('data.subscription_id'));

        $this->assertSame($firstClient->id, $firstSubscription->user_id);
        $this->assertSame($secondClient->id, $secondSubscription->user_id);
        $this->assertSame('0.00', $firstClient->wallet->fresh()->balance);
        $this->assertSame('0.00', $secondClient->wallet->fresh()->balance);
        $this->assertSame(2, WalletTransaction::where('type', 'subscription_payment')->count());
    }

    public function test_different_idempotency_keys_allow_an_intentional_renewal(): void
    {
        $client = $this->clientWithBalance('40000.00');

        $this->purchase($client, 'renewal-1')->assertOk();
        $this->purchase($client, 'renewal-2')->assertOk();

        $subscriptions = Subscription::orderBy('id')->get();

        $this->assertCount(2, $subscriptions);
        $this->assertSame(
            $subscriptions[0]->ends_at->toDateTimeString(),
            $subscriptions[1]->starts_at->toDateTimeString(),
        );
        $this->assertSame('0.00', $client->wallet->fresh()->balance);
        $this->assertSame(2, WalletTransaction::where('type', 'subscription_payment')->count());
    }

    public function test_failed_purchase_leaves_no_idempotency_key_behind(): void
    {
        $client = $this->clientWithBalance('50000.00');
        $attempts = 0;

        Subscription::creating(function () use (&$attempts): void {
            if ($attempts++ === 0) {
                throw new RuntimeException('Simulated failure.');
            }
        });

        $this->purchase($client, 'retry-1')->assertStatus(422);

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertSame('50000.00', $client->wallet->fresh()->balance);

        // The failed transaction must not reserve the key.
        $this->purchase($client, 'retry-1')->assertOk();

        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertSame('30000.00', $client->wallet->fresh()->balance);
        $this->assertSame('retry-1', Subscription::firstOrFail()->idempotency_key);
    }

    public function test_missing_or_invalid_idempotency_key_is_rejected_without_side_effects(): void
    {
        $client = $this->clientWithBalance('20000.00');

        $this->actingAs($client)->postJson('/api/v1/rci/upgrade')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->purchase($client, 'bad key!')->assertStatus(422);
        $this->purchase($client, str_repeat('a', 256))->assertStatus(422);

        $this->assertSame('20000.00', $client->wallet->fresh()->balance);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }
}
