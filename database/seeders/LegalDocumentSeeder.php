<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            [
                'title'    => 'Pasal 378 KUHP (Penipuan)',
                'content'  => 'Barang siapa dengan maksud untuk menguntungkan diri sendiri atau orang lain secara melawan hukum, dengan memakai nama palsu atau martabat palsu, dengan tipu muslihat, ataupun rangkaian kebohongan, menggerakkan orang lain untuk menyerahkan barang sesuatu kepadanya, atau supaya memberi hutang maupun menghapuskan piutang, diancam karena penipuan dengan pidana penjara paling lama empat tahun.',
                'keywords' => 'penipuan, bohong, palsu, tipu muslihat, hutang, piutang, pidana',
                'category' => 'pidana',
            ],
            [
                'title'    => 'Pasal 362 KUHP (Pencurian)',
                'content'  => 'Barang siapa mengambil barang sesuatu, yang seluruhnya atau sebagian kepunyaan orang lain, dengan maksud untuk dimiliki secara melawan hukum, diancam karena pencurian, dengan pidana penjara paling lama lima tahun atau pidana denda paling banyak sembilan ratus rupiah.',
                'keywords' => 'pencurian, mencuri, ambil barang, pidana, maling',
                'category' => 'pidana',
            ],
            [
                'title'    => 'Pasal 1365 KUHPerdata (Perbuatan Melawan Hukum)',
                'content'  => 'Tiap perbuatan yang melanggar hukum dan membawa kerugian kepada orang lain, mewajibkan orang yang menimbulkan kerugian itu karena kesalahannya untuk menggantikan kerugian tersebut.',
                'keywords' => 'perdata, pmh, perbuatan melawan hukum, ganti rugi, kerugian, salah',
                'category' => 'perdata',
            ],
            [
                'title'    => 'Pasal 1320 KUHPerdata (Syarat Sah Perjanjian)',
                'content'  => 'Supaya terjadi persetujuan yang sah, perlu dipenuhi empat syarat: 1. kesepakatan mereka yang mengikatkan dirinya; 2. kecakapan untuk membuat suatu perikatan; 3. suatu pokok persoalan tertentu; 4. suatu sebab yang tidak terlarang.',
                'keywords' => 'perdata, perjanjian, kontrak, syarat sah, batal, kesepakatan',
                'category' => 'perdata',
            ],
            [
                'title'    => 'Pasal 39 UU Perkawinan No. 1 Tahun 1974 (Perceraian)',
                'content'  => 'Perceraian hanya dapat dilakukan di depan Sidang Pengadilan setelah Pengadilan yang bersangkutan berusaha dan tidak berhasil mendamaikan kedua belah pihak. Untuk melakukan perceraian harus ada cukup alasan, bahwa antara suami isteri itu tidak akan dapat hidup rukun sebagai suami isteri.',
                'keywords' => 'keluarga, cerai, perceraian, suami istri, pengadilan, damai, talak',
                'category' => 'keluarga',
            ],
            [
                'title'    => 'Pasal 156 Ayat (1) UU Ketenagakerjaan (Pesangon PHK)',
                'content'  => 'Dalam hal terjadi pemutusan hubungan kerja, pengusaha diwajibkan membayar uang pesangon dan/atau uang penghargaan masa kerja dan uang penggantian hak yang seharusnya diterima.',
                'keywords' => 'tenaga kerja, ketenagakerjaan, phk, pesangon, upah, uang penghargaan',
                'category' => 'tenaga_kerja',
            ],
            [
                'title'    => 'Pasal 27 Ayat (3) UU ITE (Pencemaran Nama Baik)',
                'content'  => 'Setiap Orang dengan sengaja, dan tanpa hak mendistribusikan dan/atau mentransmisikan dan/atau membuat dapat diaksesnya Informasi Elektronik dan/atau Dokumen Elektronik yang memiliki muatan penghinaan dan/atau pencemaran nama baik.',
                'keywords' => 'ite, pencemaran nama baik, penghinaan, internet, sosial media, sosmed',
                'category' => 'pidana',
            ]
        ];

        foreach ($documents as $doc) {
            LegalDocument::updateOrCreate(['title' => $doc['title']], $doc);
        }
    }
}
