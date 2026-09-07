<?php

namespace Tests\Feature\Api;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_feedback_without_email(): void
    {
        $this->postJson('/api/v1/feedback', [
            'category' => 'saran',
            'message' => 'Mohon tambahkan panduan penggunaan layanan.',
        ])->assertCreated()->assertJsonPath('success', true)->assertJsonMissingPath('data');

        $this->assertDatabaseHas('feedback', [
            'category' => 'saran',
            'message' => 'Mohon tambahkan panduan penggunaan layanan.',
            'email' => null,
            'user_id' => null,
            'status' => 'baru',
        ]);
    }

    public function test_bearer_token_links_the_sender_and_ignores_internal_fields(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $other = User::factory()->create(['role' => 'admin']);

        $this->withToken($user->createToken('feedback-test')->plainTextToken)
            ->postJson('/api/v1/feedback', [
                'category' => 'kendala_teknis',
                'message' => 'Tombol pada halaman profil tidak merespons.',
                'email' => 'pengguna@example.com',
                'user_id' => $other->id,
                'status' => 'selesai',
                'admin_notes' => 'Catatan palsu',
                'reviewed_by' => $other->id,
            ])->assertCreated();

        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'email' => 'pengguna@example.com',
            'status' => 'baru',
            'admin_notes' => null,
            'reviewed_by' => null,
        ]);
    }

    public function test_invalid_category_email_and_oversized_message_are_rejected(): void
    {
        $this->postJson('/api/v1/feedback', [
            'category' => 'lainnya',
            'email' => 'alamat tidak valid',
            'message' => str_repeat('a', 5001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['category', 'email', 'message']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_empty_or_short_messages_are_rejected(): void
    {
        foreach (['   ', 'Singkat'] as $message) {
            $this->postJson('/api/v1/feedback', [
                'category' => 'kritik',
                'message' => $message,
            ])->assertUnprocessable()->assertJsonValidationErrors('message');
        }
        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_submissions_are_limited_per_ip(): void
    {
        $payload = ['category' => 'saran', 'message' => 'Masukan untuk meningkatkan layanan RCI.'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/feedback', $payload)->assertCreated();
        }
        $this->postJson('/api/v1/feedback', $payload)->assertStatus(429);
        $this->assertDatabaseCount('feedback', 5);
    }

    public function test_feedback_has_no_public_read_endpoint(): void
    {
        $feedback = Feedback::create(['category' => 'kritik', 'message' => 'Masukan pribadi dari pengunjung.']);
        $this->getJson('/api/v1/feedback')->assertStatus(405);
        $this->getJson('/api/v1/feedback/'.$feedback->id)->assertNotFound();
    }

    public function test_feedback_page_can_be_opened_directly_without_login(): void
    {
        $this->withoutVite()->get('/kritik-saran')->assertOk()->assertViewIs('app');
    }
}
