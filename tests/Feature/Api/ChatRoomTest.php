<?php

namespace Tests\Feature\Api;

use App\Models\ChatMessage;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatRoomTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $expert;
    private User $unauthorizedUser;
    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => 'client']);
        $this->expert = User::factory()->create(['role' => 'lawyer']);
        $this->unauthorizedUser = User::factory()->create(['role' => 'client']);

        $this->case = LegalCase::factory()->create([
            'client_id' => $this->client->id,
            'expert_id' => $this->expert->id,
        ]);
    }

    public function test_unauthorized_user_cannot_view_messages()
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson("/api/cases/{$this->case->id}/messages");

        $response->assertStatus(403)
                 ->assertJsonPath('success', false);
    }

    public function test_unauthorized_user_cannot_send_messages()
    {
        $response = $this->actingAs($this->unauthorizedUser)->postJson("/api/cases/{$this->case->id}/messages", [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('success', false);
    }

    public function test_client_can_send_and_view_messages()
    {
        // Send
        $response = $this->actingAs($this->client)->postJson("/api/cases/{$this->case->id}/messages", [
            'message' => 'Hello from client!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.message', 'Hello from client!')
                 ->assertJsonPath('data.sender_id', $this->client->id);

        // View
        $response = $this->actingAs($this->client)->getJson("/api/cases/{$this->case->id}/messages");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.message', 'Hello from client!');
    }

    public function test_expert_can_send_and_view_messages()
    {
        // Send
        $response = $this->actingAs($this->expert)->postJson("/api/cases/{$this->case->id}/messages", [
            'message' => 'Hello from expert!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.message', 'Hello from expert!')
                 ->assertJsonPath('data.sender_id', $this->expert->id);

        // View
        $response = $this->actingAs($this->expert)->getJson("/api/cases/{$this->case->id}/messages");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.message', 'Hello from expert!');
    }

    public function test_user_can_mark_messages_as_read()
    {
        // Expert sends a message
        $this->actingAs($this->expert)->postJson("/api/cases/{$this->case->id}/messages", [
            'message' => 'Hello from expert!',
        ]);

        // Client marks messages as read
        $response = $this->actingAs($this->client)->putJson("/api/cases/{$this->case->id}/messages/read");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        // Verify it was marked as read
        $this->assertDatabaseHas('chat_messages', [
            'message' => 'Hello from expert!',
            'is_read' => 0, // is_read is false by default in DB but we update read_at
        ]);
        
        $message = ChatMessage::where('message', 'Hello from expert!')->first();
        $this->assertNotNull($message->read_at);
    }
}
