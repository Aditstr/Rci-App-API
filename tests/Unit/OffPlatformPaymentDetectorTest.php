<?php

namespace Tests\Unit;

use App\Services\OffPlatformPaymentDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OffPlatformPaymentDetectorTest extends TestCase
{
    private OffPlatformPaymentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new OffPlatformPaymentDetector();
    }

    #[DataProvider('blockedMessageProvider')]
    public function test_blocks_high_risk_off_platform_payment_requests(string $message): void
    {
        $result = $this->detector->analyze($message);

        $this->assertTrue($result['should_flag']);
        $this->assertTrue($result['should_block']);
        $this->assertSame('high', $result['severity']);
    }

    public static function blockedMessageProvider(): array
    {
        return [
            ['Transfer langsung ke rekening BCA 1234567890 agar lebih cepat.'],
            ['Bayar di luar sistem RCI ke rekening pribadi saya 9876543210.'],
            ['Tanpa lewat RCI, langsung bayar ke akun DANA saya 081234567890.'],
        ];
    }

    #[DataProvider('safeMessageProvider')]
    public function test_allows_normal_and_safety_messages(string $message): void
    {
        $result = $this->detector->analyze($message);

        $this->assertFalse($result['should_flag']);
        $this->assertFalse($result['should_block']);
    }

    public static function safeMessageProvider(): array
    {
        return [
            ['Dokumen gugatan sudah saya periksa dan akan saya revisi hari ini.'],
            ['Pembayaran hanya melalui sistem RCI.'],
            ['Jangan transfer di luar RCI atau ke rekening pribadi siapa pun.'],
        ];
    }
}
