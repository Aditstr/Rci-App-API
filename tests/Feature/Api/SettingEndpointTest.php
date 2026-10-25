<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowlisted_settings_are_publicly_readable(): void
    {
        Setting::create(['key' => 'bank_destination', 'value' => 'BCA 1234567890']);

        $this->getJson('/api/v1/settings/bank_destination')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', 'BCA 1234567890');
    }

    public function test_non_allowlisted_settings_are_not_public(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '20000']);
        Setting::create(['key' => 'xendit_secret_key', 'value' => 'rahasia']);

        foreach (['membership_price', 'xendit_secret_key', 'unknown_key'] as $key) {
            $this->getJson('/api/v1/settings/'.$key)
                ->assertNotFound()
                ->assertJsonMissingPath('data');
        }
    }
}
