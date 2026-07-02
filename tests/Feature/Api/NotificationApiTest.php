<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\LegalCase;
use App\Notifications\CaseStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['role' => 'client']);
        $this->user2 = User::factory()->create(['role' => 'client']);

        $this->case = LegalCase::factory()->create([
            'client_id' => $this->user1->id,
            'title' => 'Sengketa Batas Tanah',
        ]);

        // Send notifications to user1
        $this->user1->notify(new CaseStatusUpdated($this->case, 'Pemberitahuan pertama'));
        $this->user1->notify(new CaseStatusUpdated($this->case, 'Pemberitahuan kedua'));

        // Send notifications to user2
        $this->user2->notify(new CaseStatusUpdated($this->case, 'Pemberitahuan untuk user lain'));
    }

    public function test_user_can_list_their_notifications()
    {
        $response = $this->actingAs($this->user1)->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.data.message', 'Pemberitahuan kedua');
    }

    public function test_user_can_filter_unread_notifications()
    {
        // First, mark one of the notifications as read
        $notification = $this->user1->unreadNotifications()->first();
        $notification->markAsRead();

        // Get unread only
        $response = $this->actingAs($this->user1)->getJson('/api/v1/notifications?unread=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_user_can_get_unread_count()
    {
        $response = $this->actingAs($this->user1)->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $notification = $this->user1->unreadNotifications()->first();

        $response = $this->actingAs($this->user1)->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(1, $this->user1->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_other_users_notification_as_read()
    {
        $notificationForUser2 = $this->user2->unreadNotifications()->first();

        $response = $this->actingAs($this->user1)->postJson("/api/v1/notifications/{$notificationForUser2->id}/read");

        $response->assertStatus(404);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $response = $this->actingAs($this->user1)->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $this->user1->unreadNotifications()->count());
    }
}
