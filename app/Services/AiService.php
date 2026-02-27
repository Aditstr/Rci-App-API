<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    // ──────────────────────────────────────────────
    // System Prompts (sent to LLM in production)
    // ──────────────────────────────────────────────

    /**
     * System prompt for FREE / Guest users.
     * Instructs the AI to answer vaguely, never cite Pasal, max 3 sentences.
     */
    private const SYSTEM_PROMPT_FREE = <<<'PROMPT'
You are RCI Legal Assistant. The user is on a FREE plan.
Rules:
- Answer generally. Explain definitions but DO NOT give specific solutions.
- Do NOT mention specific Article numbers (Pasal).
- Keep it short (max 3 sentences).
- End with a teaser: "Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami."
PROMPT;

    /**
     * System prompt for PRO members.
     * Instructs the AI to be comprehensive, cite UU/Pasal, provide action plan.
     */
    private const SYSTEM_PROMPT_PRO = <<<'PROMPT'
You are RCI Legal Expert. The user is a VIP PRO member.
Rules:
- Answer deeply and specifically.
- Cite relevant Indonesian Laws (UU/Pasal) explicitly.
- Provide a step-by-step action plan.
- Be professional and comprehensive.
PROMPT;

    // ──────────────────────────────────────────────
    // FREE-tier mock responses (vague, no Pasal)
    // ──────────────────────────────────────────────

    private const FREE_RESPONSES = [
        'pidana' => 'Permasalahan yang Anda sampaikan berkaitan dengan ranah hukum pidana. '
            . 'Secara umum, tindakan tersebut dapat dikategorikan sebagai tindak pidana yang diatur dalam undang-undang yang berlaku. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',

        'perdata' => 'Masalah ini termasuk dalam ranah hukum perdata yang berkaitan dengan hubungan antar pihak. '
            . 'Langkah awal yang umum dilakukan adalah upaya penyelesaian di luar pengadilan melalui mediasi. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',

        'keluarga' => 'Pertanyaan Anda menyangkut hukum keluarga yang mengatur hubungan dalam lingkup rumah tangga. '
            . 'Pengadilan akan mempertimbangkan berbagai faktor sebelum mengambil keputusan. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',

        'bisnis' => 'Isu yang Anda angkat terkait dengan hukum bisnis dan hubungan komersial antar pihak. '
            . 'Penting untuk meninjau kembali perjanjian yang ada sebagai langkah awal. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',

        'properti' => 'Permasalahan properti umumnya memerlukan verifikasi dokumen kepemilikan sebagai langkah pertama. '
            . 'Sengketa tanah dapat diselesaikan melalui beberapa jalur yang tersedia dalam sistem hukum kita. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',

        'tenaga_kerja' => 'Permasalahan ketenagakerjaan yang Anda alami merupakan hal yang cukup umum terjadi. '
            . 'Penyelesaian perselisihan hubungan industrial biasanya dimulai dari mekanisme bipartit. '
            . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.',
    ];

    // ──────────────────────────────────────────────
    // PRO-tier mock responses (detailed + Pasal)
    // ──────────────────────────────────────────────

    private const PRO_RESPONSES = [
        'pidana' => "**Analisis Hukum Pidana**\n\n"
            . "Berdasarkan kronologi yang Anda sampaikan, tindakan tersebut berpotensi melanggar ketentuan berikut:\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **Pasal 362 KUHP** — Pencurian: \"Barang siapa mengambil barang sesuatu, yang seluruhnya atau sebagian kepunyaan orang lain, dengan maksud untuk dimiliki secara melawan hukum, diancam karena pencurian, dengan pidana penjara paling lama lima tahun.\"\n"
            . "- **Pasal 372 KUHP** — Penggelapan (jika melibatkan barang yang dipercayakan)\n"
            . "- **Pasal 378 KUHP** — Penipuan (jika ada unsur tipu muslihat)\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Kumpulkan seluruh bukti (dokumen, screenshot, saksi).\n"
            . "2. Buat Laporan Polisi di Polsek/Polres terdekat.\n"
            . "3. Minta Surat Tanda Penerimaan Laporan (STPL).\n"
            . "4. Tunjuk kuasa hukum untuk mendampingi proses penyidikan.\n"
            . "5. Pantau perkembangan kasus melalui Sistem Informasi Penyidikan.\n\n"
            . "⏱️ **Estimasi waktu proses:** 3–6 bulan hingga tahap persidangan.",

        'perdata' => "**Analisis Hukum Perdata**\n\n"
            . "Perkara Anda termasuk dalam kategori sengketa perdata yang dapat diselesaikan melalui jalur litigasi maupun non-litigasi.\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **Pasal 1243 KUHPerdata** — Wanprestasi: ganti rugi akibat tidak dipenuhinya perikatan.\n"
            . "- **Pasal 1365 KUHPerdata** — Perbuatan Melawan Hukum (PMH).\n"
            . "- **Pasal 1320 KUHPerdata** — Syarat sah perjanjian.\n"
            . "- **UU No. 30 Tahun 1999** — Arbitrase dan Alternatif Penyelesaian Sengketa.\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Kirimkan somasi (surat peringatan) kepada pihak lawan.\n"
            . "2. Jika tidak direspons dalam 14 hari, ajukan mediasi.\n"
            . "3. Siapkan gugatan perdata ke Pengadilan Negeri.\n"
            . "4. Daftarkan gugatan dan bayar panjar biaya perkara.\n"
            . "5. Hadiri sidang mediasi wajib (PERMA No. 1 Tahun 2016).\n\n"
            . "💰 **Estimasi biaya:** Rp 500.000 – Rp 2.000.000 (panjar PN).",

        'keluarga' => "**Analisis Hukum Keluarga**\n\n"
            . "Berdasarkan pertanyaan Anda, berikut analisis hukum yang relevan:\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **UU No. 1 Tahun 1974** jo. **UU No. 16 Tahun 2019** — Perkawinan.\n"
            . "- **Pasal 39 UU Perkawinan** — Perceraian hanya dapat dilakukan di depan sidang pengadilan.\n"
            . "- **Pasal 41 UU Perkawinan** — Kewajiban pemeliharaan anak pasca perceraian.\n"
            . "- **PP No. 9 Tahun 1975** — Pelaksanaan UU Perkawinan.\n"
            . "- **Kompilasi Hukum Islam (KHI) Pasal 105** — Hak asuh anak di bawah 12 tahun.\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Ajukan permohonan cerai ke Pengadilan Agama (Islam) / Pengadilan Negeri (non-Islam).\n"
            . "2. Lampirkan bukti ketidakharmonisan (saksi, foto, bukti digital).\n"
            . "3. Hadiri sidang mediasi wajib.\n"
            . "4. Jika mediasi gagal, proses berlanjut ke pembuktian.\n"
            . "5. Ajukan tuntutan hak asuh anak dan nafkah bersamaan.\n\n"
            . "⏱️ **Estimasi waktu:** 3–6 bulan (tanpa banding).",

        'bisnis' => "**Analisis Hukum Bisnis**\n\n"
            . "Permasalahan bisnis Anda perlu ditinjau dari beberapa aspek hukum:\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **UU No. 40 Tahun 2007** — Perseroan Terbatas.\n"
            . "- **UU No. 11 Tahun 2020** jo. **PP 8/2021** — Cipta Kerja (perizinan usaha).\n"
            . "- **Pasal 1338 KUHPerdata** — Asas kebebasan berkontrak.\n"
            . "- **UU No. 5 Tahun 1999** — Larangan Praktik Monopoli dan Persaingan Usaha Tidak Sehat.\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Audit seluruh perjanjian dan dokumen korporat.\n"
            . "2. Identifikasi klausul yang merugikan atau ambigu.\n"
            . "3. Siapkan legal opinion untuk posisi hukum Anda.\n"
            . "4. Ajukan negosiasi ulang atau addendum perjanjian.\n"
            . "5. Jika gagal, pertimbangkan arbitrase (BANI) atau litigasi.\n\n"
            . "💡 **Rekomendasi:** Konsultasi dengan Advokat spesialis hukum korporat.",

        'properti' => "**Analisis Sengketa Properti**\n\n"
            . "Sengketa properti memerlukan penanganan yang cermat karena melibatkan aset bernilai tinggi.\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **UU No. 5 Tahun 1960 (UUPA)** — Pokok-Pokok Agraria.\n"
            . "- **PP No. 24 Tahun 1997** — Pendaftaran Tanah.\n"
            . "- **Pasal 19 UUPA** — Sertifikat sebagai alat bukti yang kuat.\n"
            . "- **Pasal 1366 KUHPerdata** — Tanggung jawab atas kelalaian.\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Lakukan pengecekan sertifikat di kantor BPN setempat.\n"
            . "2. Telusuri riwayat kepemilikan tanah (warkah).\n"
            . "3. Minta surat keterangan dari kelurahan/desa.\n"
            . "4. Jika ada tumpang tindih, ajukan pembatalan ke PTUN.\n"
            . "5. Daftarkan gugatan perdata di Pengadilan Negeri.\n\n"
            . "⚠️ **Penting:** Sengketa tanah memiliki daluwarsa 30 tahun (Pasal 32 PP 24/1997).",

        'tenaga_kerja' => "**Analisis Hukum Ketenagakerjaan**\n\n"
            . "Perselisihan hubungan industrial yang Anda alami diatur secara komprehensif:\n\n"
            . "📌 **Dasar Hukum:**\n"
            . "- **UU No. 13 Tahun 2003** jo. **UU No. 6 Tahun 2023** — Ketenagakerjaan.\n"
            . "- **Pasal 156 UU Ketenagakerjaan** — Komponen pesangon, UPMK, dan UPH.\n"
            . "- **UU No. 2 Tahun 2004** — Penyelesaian Perselisihan Hubungan Industrial.\n"
            . "- **PP No. 35 Tahun 2021** — PKWT, Alih Daya, Waktu Kerja, PHK.\n\n"
            . "📋 **Langkah-Langkah yang Disarankan:**\n"
            . "1. Upayakan penyelesaian secara bipartit (maks. 30 hari).\n"
            . "2. Jika gagal, ajukan mediasi ke Disnaker setempat.\n"
            . "3. Mediator mengeluarkan anjuran dalam 30 hari.\n"
            . "4. Jika masih gagal, ajukan gugatan ke Pengadilan Hubungan Industrial (PHI).\n"
            . "5. Hitung hak pesangon: 1x Pasal 156 ayat (2) + 1x ayat (3) + ayat (4).\n\n"
            . "💰 **Perhitungan Pesangon PHK:**\n"
            . "- Masa kerja 1–3 thn: 2 bulan upah\n"
            . "- Masa kerja 3–6 thn: 3 bulan upah\n"
            . "- Masa kerja 6–9 thn: 4 bulan upah",
    ];

    // ──────────────────────────────────────────────
    // Fallback responses
    // ──────────────────────────────────────────────

    private const FREE_FALLBACK = 'Terima kasih atas pertanyaan Anda. '
        . 'Topik ini berkaitan dengan aspek hukum yang memerlukan penelaahan lebih lanjut. '
        . 'Kasus ini memiliki celah hukum spesifik. Untuk panduan langkah demi langkah, hubungi Paralegal kami.';

    private const PRO_FALLBACK = "**Analisis Hukum Umum**\n\n"
        . "Pertanyaan Anda memerlukan kajian lintas bidang hukum. "
        . "Berikut langkah awal yang disarankan:\n\n"
        . "📋 **Langkah-Langkah:**\n"
        . "1. Identifikasi para pihak dan kronologi peristiwa.\n"
        . "2. Kumpulkan seluruh dokumen dan bukti yang relevan.\n"
        . "3. Tentukan apakah masalah ini masuk ranah pidana, perdata, atau administrasi.\n"
        . "4. Konsultasikan dengan Advokat yang sesuai bidang keahliannya.\n\n"
        . "💡 Silakan jelaskan kasus Anda lebih detail agar kami dapat memberikan analisis pasal yang tepat.";

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    /**
     * Generate an AI response with quality tiered by user subscription status.
     *
     * @param  string     $message  The user's question
     * @param  User|null  $user     The authenticated user (null = guest)
     * @return array{answer: string, topic: string, confidence: float, system_prompt: string, disclaimer: string}
     */
    public function chat(string $message, ?User $user = null): array
    {
        // 1. Cek User (Gue sesuaikan logika cek rolenya biar aman)
        // Kalau lu udah yakin method hasActiveSubscription() ada di Model User, pakai itu.
        // Kalau belum, pakai cek role manual ini:
        $isPro = $user && in_array($user->role ?? '', ['corporate', 'lawyer']); 
        
        $topic = $this->detectTopic($message);

        // 2. Ambil Prompt dari CONSTANT yang LU BUAT (Gak gue ubah)
        $systemPrompt = $isPro ? self::SYSTEM_PROMPT_PRO : self::SYSTEM_PROMPT_FREE;

        // 3. --- LOGIKA BARU: TEMBAK API GROQ ---
        try {
            $apiKey = env('GROQ_API_KEY');

            if ($apiKey) {
                $response = Http::withToken($apiKey)->timeout(10)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama3-8b-8192', // Model Meta yang kenceng & gratis
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.5,
                ]);

                if ($response->successful()) {
                    // Kalau Sukses, Pakai Jawaban AI
                    return [
                        'answer'        => $response->json('choices.0.message.content'),
                        'topic'         => $topic,
                        'confidence'    => $isPro ? 0.95 : 0.65, // Pro lebih yakin
                        'system_prompt' => $systemPrompt,
                        'disclaimer'    => $isPro 
                            ? 'Analisis berdasarkan hukum positif Indonesia. Konsultasikan dengan Advokat.' 
                            : 'Jawaban bersifat umum. Hubungi Paralegal kami untuk langkah teknis.',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Kalau API Error, diam saja dan lanjut ke kodingan lama lu (Fallback)
            Log::error("AI Error: " . $e->getMessage());
        }

        // 4. --- FALLBACK KE KODINGAN LAMA LU (Jaga-jaga kalau internet mati) ---
        if ($isPro) {
            return $this->buildProResponse($message, $topic);
        }

        return $this->buildFreeResponse($message, $topic);
    }

    // ──────────────────────────────────────────────
    // Response Builders
    // ──────────────────────────────────────────────

    private function buildFreeResponse(string $message, string $topic): array
    {
        $answer = self::FREE_RESPONSES[$topic] ?? self::FREE_FALLBACK;

        return [
            'answer'        => $answer,
            'topic'         => $topic,
            'confidence'    => round(mt_rand(50, 70) / 100, 2),  // lower confidence for free
            'system_prompt' => self::SYSTEM_PROMPT_FREE,          // exposed for transparency / debugging
            'disclaimer'    => 'Jawaban ini bersifat umum dan bukan merupakan nasihat hukum resmi. '
                             . 'Untuk analisis pasal mendalam dan strategi kasus, silakan upgrade ke Pro atau hubungi Paralegal kami.',
        ];
    }

    private function buildProResponse(string $message, string $topic): array
    {
        $answer = self::PRO_RESPONSES[$topic]
            ?? self::PRO_FALLBACK;

        return [
            'answer'        => $answer,
            'topic'         => $topic,
            'confidence'    => round(mt_rand(85, 98) / 100, 2),  // higher confidence for pro
            'system_prompt' => self::SYSTEM_PROMPT_PRO,
            'disclaimer'    => 'Analisis ini disusun berdasarkan hukum positif Indonesia yang berlaku. '
                             . 'Untuk kepastian hukum, konsultasikan dengan Advokat yang berpraktik.',
        ];
    }

    // ──────────────────────────────────────────────
    // Topic Detection
    // ──────────────────────────────────────────────

    /**
     * Naive keyword-based topic detection (placeholder for NLP in production).
     */
    private function detectTopic(string $message): string
    {
        $message = strtolower($message);

        $keywords = [
            'pidana'       => ['pidana', 'kriminal', 'pencurian', 'penipuan', 'penganiayaan', 'korupsi', 'pembunuhan', 'narkotika', 'penjara'],
            'perdata'      => ['perdata', 'gugatan', 'wanprestasi', 'ganti rugi', 'kontrak', 'perjanjian', 'somasi', 'pmh'],
            'keluarga'     => ['cerai', 'perceraian', 'hak asuh', 'anak', 'nikah', 'waris', 'nafkah', 'perkawinan', 'adopsi'],
            'bisnis'       => ['bisnis', 'perusahaan', 'pt', 'saham', 'investasi', 'korporat', 'direktur', 'komisaris', 'merger'],
            'properti'     => ['tanah', 'properti', 'sertifikat', 'rumah', 'sengketa lahan', 'bpn', 'shm', 'hgb', 'agraria'],
            'tenaga_kerja' => ['phk', 'karyawan', 'tenaga kerja', 'upah', 'pesangon', 'kontrak kerja', 'lembur', 'cuti', 'outsourcing'],
        ];

        foreach ($keywords as $topic => $words) {
            foreach ($words as $word) {
                if (str_contains($message, $word)) {
                    return $topic;
                }
            }
        }

        return 'umum';
    }
}
