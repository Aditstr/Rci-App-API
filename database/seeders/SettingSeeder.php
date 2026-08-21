<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $paralegalSop = <<<HTML
<h2>SOP Paralegal (Standard Operating Procedure)</h2>
<p>Selamat datang di Roys Counsel Indonesia. Sebelum Anda dapat mulai menangani kasus, Anda wajib memahami dan menyetujui SOP berikut:</p>
<ol>
    <li>Menjaga kerahasiaan identitas dan data klien (Non-Disclosure Agreement).</li>
    <li>Merespons pesan klien selambat-lambatnya 1x24 jam pada hari kerja.</li>
    <li>Berkonsultasi dengan pengacara (Lawyer) yang ditunjuk jika menemukan hambatan hukum yang di luar kewenangan paralegal.</li>
    <li>Tidak menerima pembayaran di luar sistem Roys Counsel Indonesia.</li>
</ol>
<p>Dengan mengunggah KTP dan Ijazah di bawah ini, Anda menyatakan <strong>setuju</strong> untuk tunduk pada SOP di atas.</p>
HTML;

        $lawyerSop = <<<HTML
<h2>SOP Pengacara (Lawyer)</h2>
<p>Selamat datang di Roys Counsel Indonesia. Sebelum Anda dapat mulai menangani kasus, Anda wajib memahami dan menyetujui SOP berikut:</p>
<ol>
    <li>Memberikan advis hukum yang profesional dan dapat dipertanggungjawabkan sesuai kode etik profesi advokat.</li>
    <li>Merespons pesan klien selambat-lambatnya 1x24 jam pada hari kerja.</li>
    <li>Berkoordinasi dengan paralegal untuk pengurusan dokumen administrasi.</li>
    <li>Tidak menerima pembayaran di luar sistem Roys Counsel Indonesia.</li>
</ol>
<p>Dengan mengunggah dokumen-dokumen persyaratan di bawah ini, Anda menyatakan <strong>setuju</strong> untuk tunduk pada SOP di atas.</p>
HTML;

        Setting::updateOrCreate(
            ['key' => 'paralegal_sop'],
            ['value' => $paralegalSop]
        );

        Setting::updateOrCreate(
            ['key' => 'lawyer_sop'],
            ['value' => $lawyerSop]
        );

        $this->command->info("✅ SOP Settings seeded successfully!");
    }
}
