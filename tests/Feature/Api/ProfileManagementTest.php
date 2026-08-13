<?php

namespace Tests\Feature\Api;

use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $lawyer;
    private string $clientToken;
    private string $lawyerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a verified client
        $this->client = User::factory()->create([
            'role'              => 'client',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
            'phone'             => '081234567890',
        ]);

        // Create a verified lawyer with expert profile
        $this->lawyer = User::factory()->create([
            'role'              => 'lawyer',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);
        ExpertProfile::create([
            'user_id'             => $this->lawyer->id,
            'license_number'      => 'PERADI-TEST-001',
            'verification_status' => 'approved',
            'bio'                 => 'Experienced lawyer',
            'experience_years'    => 5,
        ]);

        $this->clientToken = $this->client->createToken('test')->plainTextToken;
        $this->lawyerToken = $this->lawyer->createToken('test')->plainTextToken;
    }

    // ─── GET /api/v1/profile ────────────────────────────────────

    public function test_can_get_profile(): void
    {
        $response = $this->getJson('/api/v1/profile', [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->client->id)
            ->assertJsonPath('data.name', $this->client->name)
            ->assertJsonPath('data.email', $this->client->email)
            ->assertJsonPath('data.role', 'client');
    }

    public function test_expert_profile_includes_expert_data(): void
    {
        $response = $this->getJson('/api/v1/profile', [
            'Authorization' => "Bearer {$this->lawyerToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'lawyer')
            ->assertJsonPath('data.expert_profile.license_number', 'PERADI-TEST-001')
            ->assertJsonPath('data.expert_profile.bio', 'Experienced lawyer');
    }

    public function test_unauthenticated_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');
        $response->assertUnauthorized();
    }

    // ─── POST /api/v1/profile (Update) ──────────────────────────

    public function test_can_update_name(): void
    {
        $response = $this->postJson('/api/v1/profile', [
            'name' => 'Nama Baru',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nama Baru');

        $this->assertDatabaseHas('users', [
            'id'   => $this->client->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_can_update_phone(): void
    {
        $response = $this->postJson('/api/v1/profile', [
            'phone' => '089876543210',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.phone', '089876543210');
    }

    public function test_phone_must_be_unique(): void
    {
        // Create another user with a specific phone
        User::factory()->create(['phone' => '081111111111']);

        $response = $this->postJson('/api/v1/profile', [
            'phone' => '081111111111',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_can_upload_avatar(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Avatar URL should be set
        $this->assertNotNull($response->json('data.avatar_url'));

        // File should exist on public disk
        Storage::disk('public')->assertExists("avatars/{$this->client->id}");
    }

    public function test_avatar_validation_rejects_non_images(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/v1/profile', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_empty_update_returns_422(): void
    {
        $response = $this->postJson('/api/v1/profile', [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable();
    }

    // ─── PUT /api/v1/profile/password ───────────────────────────

    public function test_can_change_password(): void
    {
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password'      => 'password123',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Verify new password works
        $this->client->refresh();
        $this->assertTrue(Hash::check('newpassword456', $this->client->password));
    }

    public function test_wrong_current_password_fails(): void
    {
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password'      => 'wrongpassword',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password'      => 'password123',
            'password'              => 'newpassword456',
            'password_confirmation' => 'mismatch789',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_google_only_user_cannot_change_password(): void
    {
        $googleUser = User::factory()->create([
            'role'              => 'client',
            'email_verified_at' => now(),
            'password'          => null,
            'google_id'         => 'google-123',
        ]);
        $token = $googleUser->createToken('test')->plainTextToken;

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password'      => 'anything',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    // ─── PUT /api/v1/profile/expert ─────────────────────────────

    public function test_expert_can_update_bio(): void
    {
        $response = $this->putJson('/api/v1/profile/expert', [
            'bio' => 'Senior corporate lawyer with 10+ years experience.',
        ], [
            'Authorization' => "Bearer {$this->lawyerToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', 'Senior corporate lawyer with 10+ years experience.');
    }

    public function test_expert_can_update_specialization_tags(): void
    {
        $response = $this->putJson('/api/v1/profile/expert', [
            'specialization_tags' => ['Pidana', 'Perdata', 'Keluarga'],
        ], [
            'Authorization' => "Bearer {$this->lawyerToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.specialization_tags', ['Pidana', 'Perdata', 'Keluarga']);
    }

    public function test_expert_can_update_experience_years(): void
    {
        $response = $this->putJson('/api/v1/profile/expert', [
            'experience_years' => 12,
        ], [
            'Authorization' => "Bearer {$this->lawyerToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.experience_years', 12);
    }

    public function test_client_cannot_update_expert_profile(): void
    {
        $response = $this->putJson('/api/v1/profile/expert', [
            'bio' => 'I am not an expert.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertForbidden();
    }

    // ─── DELETE /api/v1/profile/avatar ──────────────────────────

    public function test_can_delete_avatar(): void
    {
        Storage::fake('public');

        // First upload an avatar
        $this->client->update([
            'avatar_url' => Storage::disk('public')->url('avatars/test/avatar.jpg'),
        ]);

        $response = $this->deleteJson('/api/v1/profile/avatar', [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($this->client->fresh()->avatar_url);
    }

    public function test_delete_avatar_when_no_avatar_returns_404(): void
    {
        $this->client->update(['avatar_url' => null]);

        $response = $this->deleteJson('/api/v1/profile/avatar', [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertNotFound();
    }
}
