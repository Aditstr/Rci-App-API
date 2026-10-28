<?php

namespace Tests\Feature;

use App\Services\AiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
    }

    private function service(): AiService
    {
        return app(AiService::class);
    }

    public function test_gemini_answer_is_returned_and_the_key_never_appears_in_the_url(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'jawaban ai']]],
                    'finishReason' => 'STOP',
                ]],
            ]),
        ]);

        $result = $this->service()->chat('Apa itu wanprestasi?');

        $this->assertFalse($result['fallback']);
        $this->assertSame('jawaban ai', $result['answer']);

        Http::assertSent(function ($request) {
            return str_starts_with(
                $request->url(),
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
            )
                && ! str_contains($request->url(), 'test-gemini-key')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key');
        });

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openrouter.ai'));
    }

    public function test_provider_error_statuses_fall_back(): void
    {
        foreach ([400, 401, 403, 429, 500, 503] as $status) {
            Http::fake([
                'generativelanguage.googleapis.com/*' => Http::response([
                    'error' => ['status' => 'FAILED', 'message' => 'provider error'],
                ], $status),
            ]);

            $result = $this->service()->chat('Apa itu wanprestasi?');

            $this->assertTrue($result['fallback'], "HTTP {$status} should fall back");
            $this->assertNotSame('', $result['answer']);
        }
    }

    public function test_connection_timeout_falls_back_and_redacts_the_key(): void
    {
        Log::spy();

        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException(
                    'cURL error 28: Operation timed out after 45001 milliseconds '
                    . 'for https://generativelanguage.googleapis.com/v1beta/models/x:generateContent?key=test-gemini-key'
                );
            },
        ]);

        $result = $this->service()->chat('Apa itu wanprestasi?');

        $this->assertTrue($result['fallback']);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Gemini request failed.'
                    && $context['exception'] === ConnectionException::class
                    && ! str_contains($context['message'], 'test-gemini-key')
                    && str_contains($context['message'], '[redacted]');
            });
    }

    public function test_invalid_or_empty_responses_fall_back(): void
    {
        $payloads = [
            'no candidates' => ['candidates' => []],
            'blocked by safety' => ['candidates' => [['finishReason' => 'SAFETY']]],
            'empty text' => ['candidates' => [['content' => ['parts' => [['text' => '']]]]]],
        ];

        foreach ($payloads as $case => $payload) {
            Http::fake(['generativelanguage.googleapis.com/*' => Http::response($payload)]);

            $result = $this->service()->chat('Apa itu wanprestasi?');

            $this->assertTrue($result['fallback'], $case);
        }
    }

    public function test_missing_api_key_does_not_call_any_provider(): void
    {
        config(['services.gemini.api_key' => '']);
        Http::fake();

        $result = $this->service()->chat('Apa itu wanprestasi?');

        $this->assertTrue($result['fallback']);
        Http::assertNothingSent();
    }
}
