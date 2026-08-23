<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class OffPlatformPaymentDetector
{
    /**
     * Detect narrowly-scoped payment solicitation signals.
     * This is a triage mechanism: only high-risk expert messages are blocked;
     * medium-risk results are delivered and queued for human review.
     *
     * @return array{score:int,severity:string,signals:array<int,string>,should_flag:bool,should_block:bool}
     */
    public function analyze(string $message): array
    {
        $text = Str::lower(Str::ascii($message));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        // Remove clear safety reminders so they do not trigger the detector themselves.
        $text = preg_replace([
            '/\bjangan (?:transfer|bayar) di luar (?:aplikasi |sistem )?rci\b/u',
            '/\b(?:pembayaran|transfer) hanya (?:melalui|lewat) (?:sistem )?rci\b/u',
            '/\bwajib (?:melalui|lewat|menggunakan) (?:sistem )?rci\b/u',
            '/\btidak menerima pembayaran di luar (?:aplikasi |sistem )?rci\b/u',
        ], ' ', $text) ?? $text;

        $signals = [];
        $score = 0;

        $this->addSignal(
            $text,
            '/\b(transfer|bayar|pembayaran|dp|uang muka|fee|honorarium)\b/u',
            'payment_request',
            25,
            $signals,
            $score,
        );

        $this->addSignal(
            $text,
            '/\b(di luar (?:aplikasi |sistem )?rci|tanpa (?:melalui|lewat) (?:sistem )?rci|jangan (?:melalui|lewat) (?:sistem )?rci|bypass rci|transfer langsung|bayar langsung|langsung (?:transfer|bayar)|langsung ke (?:rekening|akun|saya|kami))\b/u',
            'platform_bypass',
            45,
            $signals,
            $score,
        );

        $this->addSignal(
            $text,
            '/\b(rekening (?:saya|kami|pribadi)|akun (?:dana|ovo|gopay|shopeepay) (?:saya|kami)|atas nama pribadi)\b/u',
            'personal_destination',
            25,
            $signals,
            $score,
        );

        $this->addSignal(
            $text,
            '/\b(?:no\.?\s*rek(?:ening)?|norek|rekening|bca|bri|bni|mandiri|cimb|qris|dana|ovo|gopay|shopeepay)[^0-9]{0,25}(?:\d[\s.\-]?){8,18}\b/u',
            'payment_account_details',
            35,
            $signals,
            $score,
        );

        $this->addSignal(
            $text,
            '/\b(whatsapp|wa|telegram)\b|(?:\+?62|0)8\d(?:[\s.\-]?\d){7,11}/u',
            'external_contact',
            10,
            $signals,
            $score,
        );

        $score = min($score, 100);

        $severity = match (true) {
            $score >= 70 => 'high',
            $score >= 45 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'severity' => $severity,
            'signals' => $signals,
            'should_flag' => $score >= 45,
            'should_block' => $score >= 70,
        ];
    }

    /** @param array<int, string> $signals */
    private function addSignal(
        string $text,
        string $pattern,
        string $signal,
        int $weight,
        array &$signals,
        int &$score,
    ): void {
        if (preg_match($pattern, $text) === 1) {
            $signals[] = $signal;
            $score += $weight;
        }
    }
}
