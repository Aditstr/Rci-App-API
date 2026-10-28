<?php

namespace Tests\Feature\Api;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AiQuotaService;
use App\Services\MembershipConfig;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiQuotaTest extends TestCase
{
    use RefreshDatabase;

    private bool $providerUp = true;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-27 10:00:00'); // 17:00 WIB

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'test-model',
        ]);

        Http::fake(function () {
            return $this->providerUp
                ? Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'jawaban ai']]]],
                    ],
                ])
                : Http::response('provider down', 500);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function member(string $role = 'client'): User
    {
        $user = User::factory()->create(['role' => $role]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_name' => 'pro',
            'price' => '20000.00',
            'currency' => 'IDR',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        return $user;
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function ask(string $url, User $user)
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token($user))
            ->postJson($url, ['message' => 'Apa itu wanprestasi?']);
    }

    public function test_active_member_gets_three_questions_per_day(): void
    {
        $user = $this->member();

        foreach ([2, 1, 0] as $remaining) {
            $response = $this->ask('/api/v1/chat/send', $user);

            $response->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('tier', 'pro')
                ->assertJsonPath('usage.limit', 3)
                ->assertJsonPath('usage.remaining', $remaining);
        }
    }

    public function test_fourth_question_is_rejected(): void
    {
        $user = $this->member();

        for ($i = 0; $i < 3; $i++) {
            $this->ask('/api/v1/chat/send', $user)->assertOk();
        }

        $this->ask('/api/v1/chat/send', $user)
            ->assertStatus(429)
            ->assertJsonPath('status', 'forbidden')
            ->assertJsonPath('usage.remaining', 0);
    }

    public function test_client_without_subscription_is_denied(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $token = $this->token($client);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/rci/chat', ['message' => 'Apa itu wanprestasi?'])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('tier', 'free');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/chat/send', ['message' => 'Apa itu wanprestasi?'])
            ->assertStatus(403)
            ->assertJsonPath('status', 'forbidden')
            ->assertJsonPath('tier', 'free');

        Http::assertNothingSent();
    }

    public function test_guest_is_denied(): void
    {
        $this->postJson('/api/v1/chat/send', [
            'message' => 'Apa itu wanprestasi?',
            'session_id' => 'guest-session',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'forbidden')
            ->assertJsonPath('tier', 'guest');

        Http::assertNothingSent();
    }

    public function test_lawyer_and_corporate_without_subscription_are_not_pro(): void
    {
        foreach (['lawyer', 'corporate'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->ask('/api/v1/rci/chat', $user)
                ->assertStatus(403)
                ->assertJsonPath('success', false)
                ->assertJsonPath('tier', 'free');
        }

        Http::assertNothingSent();
    }

    public function test_both_endpoints_share_the_same_counter(): void
    {
        $user = $this->member();

        $this->ask('/api/v1/chat/send', $user)
            ->assertOk()
            ->assertJsonPath('usage.remaining', 2);

        $this->ask('/api/v1/rci/chat', $user)
            ->assertOk()
            ->assertJsonPath('usage.remaining', 1);

        $this->ask('/api/v1/chat/send', $user)
            ->assertOk()
            ->assertJsonPath('usage.remaining', 0);

        $this->ask('/api/v1/rci/chat', $user)
            ->assertStatus(429)
            ->assertJsonPath('usage.remaining', 0);
    }

    public function test_quota_resets_at_jakarta_midnight(): void
    {
        $user = $this->member();

        Carbon::setTestNow('2026-10-27 16:59:00'); // 23:59 WIB

        for ($i = 0; $i < 3; $i++) {
            $this->ask('/api/v1/chat/send', $user)->assertOk();
        }

        $this->ask('/api/v1/chat/send', $user)->assertStatus(429);

        Carbon::setTestNow('2026-10-27 17:00:00'); // 00:00 WIB, next day

        $this->ask('/api/v1/chat/send', $user)
            ->assertOk()
            ->assertJsonPath('usage.used', 1)
            ->assertJsonPath('usage.remaining', 2);
    }

    public function test_limit_comes_from_membership_config(): void
    {
        MembershipConfig::update([
            'price' => MembershipConfig::DEFAULT_PRICE,
            'duration_days' => MembershipConfig::DEFAULT_DURATION_DAYS,
            'ai_daily_limit' => 5,
            'enabled' => true,
        ]);

        $user = $this->member();

        for ($i = 0; $i < 5; $i++) {
            $this->ask('/api/v1/chat/send', $user)
                ->assertOk()
                ->assertJsonPath('usage.limit', 5);
        }

        $this->ask('/api/v1/chat/send', $user)->assertStatus(429);
    }

    public function test_provider_failure_refunds_the_quota(): void
    {
        $user = $this->member();

        $this->providerUp = false;

        $this->ask('/api/v1/rci/chat', $user)
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('usage.used', 0)
            ->assertJsonPath('usage.remaining', 3);

        $this->providerUp = true;

        $this->ask('/api/v1/rci/chat', $user)
            ->assertOk()
            ->assertJsonPath('usage.used', 1)
            ->assertJsonPath('usage.remaining', 2);
    }

    public function test_cache_error_is_not_open_access(): void
    {
        $user = $this->member();

        $store = new class extends ArrayStore
        {
            public function lock($name, $seconds = 0, $owner = null)
            {
                throw new \RuntimeException('cache store unavailable');
            }
        };

        $this->app->instance(
            AiQuotaService::class,
            new AiQuotaService(new Repository($store)),
        );

        $this->ask('/api/v1/rci/chat', $user)
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->ask('/api/v1/chat/send', $user)
            ->assertStatus(503)
            ->assertJsonPath('status', 'error');

        Http::assertNothingSent();
    }

    /**
     * SQLite in-memory cannot run true parallel requests, so this drives the
     * shared counter directly and proves an exhausted counter is never exceeded
     * nor left inflated. Cross-process atomicity must be verified on PostgreSQL.
     */
    public function test_exhausted_counter_is_never_exceeded(): void
    {
        $user = $this->member();
        $service = app(AiQuotaService::class);

        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($service->consume($user)['allowed']);
        }

        $denied = $service->consume($user);

        $this->assertFalse($denied['allowed']);
        $this->assertSame('quota_exhausted', $denied['reason']);
        $this->assertSame(3, $service->snapshot($user)['used']);
    }

    public function test_atomic_database_store_enforces_the_limit(): void
    {
        $user = $this->member();
        $service = new AiQuotaService(Cache::store('database'));

        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($service->consume($user)['allowed']);
        }

        $this->assertFalse($service->consume($user)['allowed']);
        $this->assertSame(3, $service->snapshot($user)['used']);
    }

    public function test_redis_store_uses_the_atomic_increment_path(): void
    {
        $user = $this->member();

        $store = Mockery::mock(RedisStore::class);
        $store->shouldReceive('add')->times(4)->andReturn(true);
        $store->shouldReceive('increment')->times(4)->andReturn(1, 2, 3, 4);
        $store->shouldReceive('decrement')->once()->andReturn(3);
        $store->shouldNotReceive('lock');

        $service = new AiQuotaService(new Repository($store));

        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($service->consume($user)['allowed']);
        }

        $this->assertFalse($service->consume($user)['allowed']);
    }

    public function test_atomic_decrement_failure_after_overshoot_throws(): void
    {
        $user = $this->member();

        $store = Mockery::mock(DatabaseStore::class);
        $store->shouldReceive('add')->once()->andReturn(true);
        $store->shouldReceive('increment')->once()->andReturn(4);
        $store->shouldReceive('decrement')->once()->andReturn(false);

        $service = new AiQuotaService(new Repository($store));

        $this->expectException(RuntimeException::class);
        $service->consume($user);
    }

    public function test_atomic_refund_failure_throws(): void
    {
        $user = $this->member();

        $store = Mockery::mock(DatabaseStore::class);
        $store->shouldReceive('add')->once()->andReturn(true);
        $store->shouldReceive('increment')->once()->andReturn(1);
        $store->shouldReceive('decrement')->once()->andReturn(false);

        $service = new AiQuotaService(new Repository($store));

        $consumption = $service->consume($user);
        $this->assertTrue($consumption['allowed']);

        $this->expectException(RuntimeException::class);
        $service->refund($consumption);
    }

    public function test_atomic_decrement_to_zero_is_success(): void
    {
        $user = $this->member();

        $store = Mockery::mock(DatabaseStore::class);
        $store->shouldReceive('add')->once()->andReturn(true);
        $store->shouldReceive('increment')->once()->andReturn(1);
        $store->shouldReceive('decrement')->once()->andReturn(0);
        $store->shouldReceive('get')->once()->andReturn(0);

        $service = new AiQuotaService(new Repository($store));

        $consumption = $service->consume($user);
        $usage = $service->refund($consumption);

        $this->assertSame(0, $usage['used']);
        $this->assertSame(3, $usage['remaining']);
    }

    public function test_refund_failure_is_a_service_failure_not_a_success(): void
    {
        $user = $this->member();
        $this->providerUp = false;

        foreach (['/api/v1/chat/send', '/api/v1/rci/chat'] as $url) {
            $store = Mockery::mock(DatabaseStore::class);
            $store->shouldReceive('add')->once()->andReturn(true);
            $store->shouldReceive('increment')->once()->andReturn(1);
            $store->shouldReceive('decrement')->once()->andReturn(false);

            $this->app->instance(
                AiQuotaService::class,
                new AiQuotaService(new Repository($store)),
            );

            $this->ask($url, $user)->assertStatus(503);
        }
    }

    public function test_null_store_never_grants_ai_access(): void
    {
        $user = $this->member();

        $this->app->instance(
            AiQuotaService::class,
            new AiQuotaService(new Repository(new NullStore())),
        );

        $this->ask('/api/v1/rci/chat', $user)
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->ask('/api/v1/rci/chat', $user)
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_store_that_fails_to_persist_the_counter_is_not_open_access(): void
    {
        $user = $this->member();

        $store = new class extends ArrayStore
        {
            public function put($key, $value, $seconds)
            {
                return false;
            }
        };

        $this->app->instance(
            AiQuotaService::class,
            new AiQuotaService(new Repository($store)),
        );

        $this->ask('/api/v1/chat/send', $user)
            ->assertStatus(503)
            ->assertJsonPath('status', 'error');

        Http::assertNothingSent();
    }

    public function test_array_store_still_enforces_sequential_requests_in_testing(): void
    {
        $user = $this->member();
        $service = new AiQuotaService(new Repository(new ArrayStore()));

        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($service->consume($user)['allowed']);
        }

        $this->assertFalse($service->consume($user)['allowed']);
        $this->assertSame(3, $service->snapshot($user)['used']);
    }

    public function test_array_store_is_rejected_outside_testing(): void
    {
        $user = $this->member();
        $originalEnv = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $this->app->instance(
                AiQuotaService::class,
                new AiQuotaService(new Repository(new ArrayStore())),
            );

            $this->ask('/api/v1/rci/chat', $user)
                ->assertStatus(503)
                ->assertJsonPath('success', false);
        } finally {
            $this->app['env'] = $originalEnv;
        }

        Http::assertNothingSent();
    }
}
