<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $paralegalSop = <<<HTML
<p>Selamat datang di Roys Counsel Indonesia. Sebelum Anda dapat mulai menangani kasus, Anda wajib memahami dan menyetujui SOP berikut:</p>
<ul>
    <li>Menjaga <strong>kerahasiaan identitas</strong> dan data klien (Non-Disclosure Agreement).</li>
    <li>Merespons pesan klien selambat-lambatnya <strong>1x24 jam</strong> pada hari kerja.</li>
    <li>Berkonsultasi dengan <strong>pengacara (Lawyer)</strong> yang ditunjuk jika menemukan hambatan hukum di luar kewenangan.</li>
    <li><strong>Dilarang keras</strong> menerima pembayaran di luar sistem Roys Counsel Indonesia.</li>
</ul>
<p>Dengan mengunggah dokumen di bawah ini, Anda menyatakan <strong>setuju</strong> untuk tunduk pada SOP di atas.</p>
HTML;

        $lawyerSop = <<<HTML
<p>Selamat datang di Roys Counsel Indonesia. Sebelum Anda dapat mulai menangani kasus, Anda wajib memahami dan menyetujui SOP berikut:</p>
<ul>
    <li>Memberikan advis hukum yang profesional sesuai <strong>kode etik profesi</strong> advokat.</li>
    <li>Merespons pesan klien selambat-lambatnya <strong>1x24 jam</strong> pada hari kerja.</li>
    <li>Berkoordinasi dengan <strong>paralegal</strong> untuk pengurusan dokumen administrasi.</li>
    <li><strong>Dilarang keras</strong> menerima pembayaran di luar sistem Roys Counsel Indonesia.</li>
</ul>
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
