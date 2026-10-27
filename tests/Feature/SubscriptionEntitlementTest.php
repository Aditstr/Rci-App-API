<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionEntitlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-27 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function subscribe(User $user, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_name' => 'pro',
            'price' => '20000.00',
            'currency' => 'IDR',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ], $overrides));
    }

    private function systemPromptFor(AiService $service, User $user): string
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'jawaban']]]],
                ],
            ]),
        ]);

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'test-model',
            'services.openrouter.api_key' => null,
        ]);

        $service->chat('Apa itu wanprestasi?', $user);

        return Http::recorded()->first()[0]['system_instruction']['parts'][0]['text'];
    }

    public function test_client_with_active_subscription_is_a_member(): void
    {
        $client = $this->userWithRole('client');
        $subscription = $this->subscribe($client);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($client->hasActiveSubscription());
    }

    public function test_expired_subscription_does_not_grant_membership(): void
    {
        $client = $this->userWithRole('client');
        $subscription = $this->subscribe($client, [
            'starts_at' => now()->subDays(31),
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($client->hasActiveSubscription());
    }

    public function test_cancelled_subscription_does_not_grant_membership(): void
    {
        $client = $this->userWithRole('client');
        $subscription = $this->subscribe($client, [
            'status' => 'cancelled',
            'cancelled_at' => now()->subHour(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($client->hasActiveSubscription());
    }

    public function test_subscription_that_has_not_started_is_not_active(): void
    {
        $client = $this->userWithRole('client');
        $subscription = $this->subscribe($client, [
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($client->hasActiveSubscription());
    }

    public function test_subscription_ends_exactly_now_is_not_active(): void
    {
        $client = $this->userWithRole('client');
        $subscription = $this->subscribe($client, [
            'starts_at' => now()->subDay(),
            'ends_at' => now(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($client->hasActiveSubscription());
    }

    public function test_lawyer_without_subscription_is_not_a_member(): void
    {
        $lawyer = $this->userWithRole('lawyer');

        $this->assertFalse($lawyer->hasActiveSubscription());
    }

    public function test_corporate_without_subscription_is_not_a_member(): void
    {
        $corporate = $this->userWithRole('corporate');

        $this->assertFalse($corporate->hasActiveSubscription());
    }

    public function test_checking_membership_does_not_change_the_user_role(): void
    {
        $lawyer = $this->userWithRole('lawyer');
        $this->subscribe($lawyer);

        $corporate = $this->userWithRole('corporate');

        $this->assertTrue($lawyer->hasActiveSubscription());
        $this->assertFalse($corporate->hasActiveSubscription());

        $this->assertSame('lawyer', $lawyer->fresh()->role);
        $this->assertSame('corporate', $corporate->fresh()->role);
        $this->assertSame(1, Subscription::where('user_id', $lawyer->id)->count());
    }

    public function test_ai_service_uses_subscription_status_instead_of_role(): void
    {
        $service = app(AiService::class);

        $lawyer = $this->userWithRole('lawyer');
        $corporate = $this->userWithRole('corporate');

        $this->assertStringContainsString(
            'RCI Legal Assistant',
            $this->systemPromptFor($service, $lawyer),
        );
        $this->assertStringContainsString(
            'RCI Legal Assistant',
            $this->systemPromptFor($service, $corporate),
        );

        $this->subscribe($lawyer);

        $this->assertStringContainsString(
            'RCI Legal Expert',
            $this->systemPromptFor($service, $lawyer),
        );
    }

    public function test_database_failure_is_not_treated_as_a_user_without_membership(): void
    {
        $client = $this->userWithRole('client');

        Schema::rename('subscriptions', 'subscriptions_entitlement_probe');

        $thrown = null;

        try {
            $client->hasActiveSubscription();
        } catch (QueryException $e) {
            $thrown = $e;
        } finally {
            Schema::rename('subscriptions_entitlement_probe', 'subscriptions');
        }

        $this->assertInstanceOf(QueryException::class, $thrown);
    }
}
