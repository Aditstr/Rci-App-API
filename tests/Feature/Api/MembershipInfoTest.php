<?php

namespace Tests\Feature\Api;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AiQuotaService;
use App\Services\MembershipConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MembershipInfoTest extends TestCase
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

    public function test_guest_cannot_read_membership_info(): void
    {
        $this->getJson('/api/v1/rci/membership')->assertUnauthorized();
    }

    public function test_client_receives_backend_config_and_inactive_status(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)
            ->getJson('/api/v1/rci/membership')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.config.price', 20000)
            ->assertJsonPath('data.config.duration_days', 30)
            ->assertJsonPath('data.config.ai_daily_limit', 3)
            ->assertJsonPath('data.config.enabled', true)
            ->assertJsonPath('data.status.is_pro', false)
            ->assertJsonPath('data.status.plan', 'free')
            ->assertJsonPath('data.usage.limit', 3)
            ->assertJsonPath('data.usage.remaining', 0)
            ->assertJsonPath('data.can_purchase', true)
            ->assertJsonMissingPath('data.config.xendit_secret_key');

        // Reading membership must not create a wallet as a side effect.
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_active_subscription_reports_expiry_and_remaining_quota(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        Subscription::create([
            'user_id' => $client->id,
            'plan_name' => 'pro',
            'price' => '20000.00',
            'currency' => 'IDR',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(10),
        ]);

        $quota = app(AiQuotaService::class);
        $quota->consume($client);
        $quota->consume($client);

        $this->actingAs($client)
            ->getJson('/api/v1/rci/membership')
            ->assertOk()
            ->assertJsonPath('data.status.is_pro', true)
            ->assertJsonPath('data.status.plan', 'pro')
            ->assertJsonPath('data.status.days_left', 10)
            ->assertJsonPath('data.usage.limit', 3)
            ->assertJsonPath('data.usage.used', 2)
            ->assertJsonPath('data.usage.remaining', 1)
            ->assertJsonStructure([
                'data' => [
                    'status' => ['starts_at', 'ends_at'],
                ],
            ]);
    }

    public function test_config_changes_are_reflected_without_a_new_endpoint(): void
    {
        MembershipConfig::update([
            'price' => 35000,
            'duration_days' => 60,
            'ai_daily_limit' => 5,
            'enabled' => false,
        ]);

        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)
            ->getJson('/api/v1/rci/membership')
            ->assertOk()
            ->assertJsonPath('data.config.price', 35000)
            ->assertJsonPath('data.config.duration_days', 60)
            ->assertJsonPath('data.config.ai_daily_limit', 5)
            ->assertJsonPath('data.config.enabled', false);
    }

    public function test_non_client_can_read_status_but_cannot_purchase(): void
    {
        $lawyer = User::factory()->create(['role' => 'lawyer']);

        $this->actingAs($lawyer)
            ->getJson('/api/v1/rci/membership')
            ->assertOk()
            ->assertJsonPath('data.can_purchase', false);
    }
}
