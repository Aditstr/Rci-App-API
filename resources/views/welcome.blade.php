@extends('layouts.app')
@section('title', 'RCI — Roys Counsel Indonesia | Konsultasi Hukum Cerdas')
@section('meta_description', 'Platform konsultasi hukum cerdas Indonesia. Konsultasi dengan AI, Paralegal, dan Pengacara profesional dalam satu ekosistem terpadu.')

@section('content')

<!-- ════════════════════════════════
     HERO
════════════════════════════════ -->
<section class="hero-section">
    <div class="page-container">
        <div class="hero-grid">

            <!-- Left: Text -->
            <div style="opacity:0;" class="fade-in-up" id="hero-text">
                <span class="tag" style="margin-bottom:20px; display:inline-flex;">⚖️ &nbsp;Platform Hukum #1 Indonesia</span>

                <h1 class="font-display text-display" style="color:var(--color-obsidian); margin-bottom:24px;">
                    HUKUM<br>
                    <span style="color:var(--color-ember);">UNTUK<br>SEMUA</span>
                </h1>

                <p style="font-size:18px; color:rgba(7,6,7,0.65); max-width:420px; margin-bottom:40px; line-height:1.6;">
                    Konsultasi hukum cerdas dengan dukungan AI, Paralegal profesional, dan Pengacara berpengalaman — dari mana saja, kapan saja.
                </p>

                <div style="display:flex; gap:16px; flex-wrap:wrap;">
                    <a href="/register" class="btn-primary" style="font-size:18px; padding:16px 32px;">
                        Mulai Konsultasi Gratis
                    </a>
                    <a href="/#cara-kerja" class="btn-secondary">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>

            <!-- Right: Halftone Hero Block -->
            <div style="opacity:0;" class="fade-in-up animate-delay-2" id="hero-visual">
                <div class="halftone-block" style="height:480px; display:flex; flex-direction:column; justify-content:flex-end; padding:40px;">
                    <div class="halftone-overlay"></div>
                    <!-- Decorative big number -->
                    <div style="position:absolute; top:32px; right:32px; font-family:var(--font-display); font-size:clamp(60px,8vw,120px); color:rgba(255,255,255,0.15); line-height:1; letter-spacing:0.02em; pointer-events:none;">RCI</div>
                    <!-- Quote card -->
                    <div style="position:relative; background:rgba(255,255,255,0.12); backdrop-filter:blur(12px); border-radius:20px; padding:24px; z-index:2;">
                        <p style="color:var(--color-chalk); font-size:16px; line-height:1.6; margin-bottom:12px;">"Hak hukum Anda tidak boleh tergantung pada kemampuan membayar pengacara mahal."</p>
                        <p style="color:rgba(255,255,255,0.7); font-size:13px;">— Prinsip RCI</p>
                    </div>
                </div>

                <!-- Live chat indicator -->
                <div style="display:flex; align-items:center; gap:12px; margin-top:16px; padding:16px 24px; background:var(--color-limestone); border-radius:var(--radius-medium);">
                    <div style="width:10px;height:10px;border-radius:50%;background:var(--color-ember);animation:pulse-ember 2s infinite;"></div>
                    <span style="font-size:14px; font-weight:500;">AI Konsultasi aktif — respons dalam detik</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════
     STATS BAR
════════════════════════════════ -->
<section style="padding: 0 0 80px;">
    <div class="page-container">
        <div class="stat-grid">
            <div class="stat-card fade-in-up animate-delay-1">
                <p class="stat-card-label">Kasus Ditangani</p>
                <p class="stat-card-value">2.4K+</p>
            </div>
            <div class="stat-card fade-in-up animate-delay-2">
                <p class="stat-card-label">Ahli Hukum</p>
                <p class="stat-card-value">180+</p>
            </div>
            <div class="stat-card fade-in-up animate-delay-3">
                <p class="stat-card-label">Tingkat Kepuasan</p>
                <p class="stat-card-value">97%</p>
            </div>
            <div class="card fade-in-up animate-delay-4" style="background:var(--color-plasma-violet); position:relative; overflow:hidden;">
                <div class="halftone-overlay" style="opacity:0.3;"></div>
                <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Rata-rata Respons</p>
                <p class="stat-card-value" style="font-size:clamp(40px,5vw,80px);">&lt; 2 Mnt</p>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════
     LAYANAN
════════════════════════════════ -->
<section id="layanan" class="section">
    <div class="page-container">
        <div style="margin-bottom:48px;">
            <span class="tag" style="margin-bottom:16px;">Layanan Kami</span>
            <h2 class="font-display text-2xl-heading" style="color:var(--color-obsidian);">TIGA LAPIS<br>PERLINDUNGAN HUKUM</h2>
        </div>

        <div class="card-grid">
            <!-- AI Consultation -->
            <div class="card fade-in-up" style="display:flex; flex-direction:column; gap:20px;">
                <div style="width:56px;height:56px;background:var(--color-ember);border-radius:var(--radius-medium);display:flex;align-items:center;justify-content:center;">
                    <svg width="28" height="28" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <span class="tag" style="margin-bottom:12px;">Freemium & Pro</span>
                    <h3 class="font-display text-heading" style="margin-bottom:12px;">KONSULTASI AI</h3>
                    <p style="color:rgba(7,6,7,0.65); font-size:14px; line-height:1.7;">
                        Engine AI berjenjang dengan basis pengetahuan hukum Indonesia. Freemium mendapat akses terbatas, Pro Member menikmati konsultasi tak terbatas dengan respons mendalam.
                    </p>
                </div>
                <a href="/register" class="btn-primary" style="margin-top:auto; align-self:flex-start;">Coba Gratis →</a>
            </div>

            <!-- Paralegal -->
            <div class="card-plasma fade-in-up animate-delay-1" style="display:flex; flex-direction:column; gap:20px; position:relative; overflow:hidden;">
                <div class="halftone-overlay" style="opacity:0.2;"></div>
                <div style="position:relative; z-index:1;">
                    <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:var(--radius-medium);display:flex;align-items:center;justify-content:center; margin-bottom:20px;">
                        <svg width="28" height="28" fill="none" stroke="white" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <span class="tag" style="background:rgba(255,255,255,0.2); color:white; margin-bottom:12px;">Terverifikasi</span>
                    <h3 class="font-display text-heading" style="color:white; margin-bottom:12px;">BANTUAN PARALEGAL</h3>
                    <p style="color:rgba(255,255,255,0.75); font-size:14px; line-height:1.7;">
                        Paralegal terverifikasi siap membantu kasus Anda. Sistem Kanban-board memastikan progres kasus terpantau real-time.
                    </p>
                    <a href="/register" class="btn-primary" style="margin-top:24px; display:inline-flex;">Ajukan Kasus →</a>
                </div>
            </div>

            <!-- Lawyer -->
            <div class="card fade-in-up animate-delay-2" style="display:flex; flex-direction:column; gap:20px;">
                <div style="width:56px;height:56px;background:var(--color-obsidian);border-radius:var(--radius-medium);display:flex;align-items:center;justify-content:center;">
                    <svg width="28" height="28" fill="none" stroke="white" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <span class="tag" style="margin-bottom:12px;">Profesional</span>
                    <h3 class="font-display text-heading" style="margin-bottom:12px;">PENGACARA AHLI</h3>
                    <p style="color:rgba(7,6,7,0.65); font-size:14px; line-height:1.7;">
                        Kasus kompleks dieskalasi ke Pengacara berlisensi. Sistem escrow & quotation transparan menjamin keamanan pembayaran dan fee split yang adil.
                    </p>
                </div>
                <a href="/register" class="btn-secondary" style="margin-top:auto; align-self:flex-start;">Konsultasi Sekarang →</a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════
     CARA KERJA
════════════════════════════════ -->
<section id="cara-kerja" class="section cara-kerja-section" style="background-color:var(--color-limestone);">
    <div class="page-container">
        <div style="text-align:center; margin-bottom:48px;">
            <span class="tag" style="margin-bottom:16px;">Cara Kerja</span>
            <h2 class="font-display text-2xl-heading" style="color:var(--color-obsidian);">EMPAT LANGKAH<br>MENUJU KEADILAN</h2>
        </div>

        <div class="steps-grid">
            <!-- Connecting line (desktop only) -->
            <div class="steps-connecting-line hide-mobile"></div>

            @foreach([
                ['01','Daftar Akun', 'Registrasi gratis dalam 2 menit. Verifikasi email dan mulai konsultasi pertama Anda.'],
                ['02','Ceritakan Masalah', 'Deskripsikan kasus hukum Anda. AI kami akan memberikan analisis awal instan.'],
                ['03','Terhubung ke Ahli', 'Paralegal atau Pengacara yang tepat ditugaskan berdasarkan jenis dan kompleksitas kasus.'],
                ['04','Selesaikan Kasus', 'Pantau progres real-time, bayar aman via escrow, dan beri ulasan setelah selesai.'],
            ] as $i => $step)
            <div class="step-card">
                <div class="step-number-circle" style="background:{{ $i === 0 ? 'var(--color-ember)' : 'var(--color-obsidian)' }};">{{ $step[0] }}</div>
                <h3 class="font-display" style="font-size:20px; margin-bottom:8px; letter-spacing:0.02em;">{{ strtoupper($step[1]) }}</h3>
                <p style="font-size:14px; color:rgba(7,6,7,0.6); line-height:1.6;">{{ $step[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ════════════════════════════════
     TENTANG / CTA
════════════════════════════════ -->
<section id="tentang" class="section">
    <div class="page-container">
        <div class="cta-banner-card">
            <!-- Background halftone -->
            <div class="cta-halftone-bg hide-mobile"></div>
            <div class="cta-halftone-overlay hide-mobile">
                <div class="halftone-overlay" style="border-radius:24px; opacity:0.4;"></div>
            </div>

            <div style="position:relative; z-index:1;">
                <span class="tag" style="background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.7); margin-bottom:20px;">Tentang RCI</span>
                <h2 class="font-display text-2xl-heading" style="color:var(--color-chalk); margin-bottom:24px;">KEADILAN<br>BUKAN PRIVILEGE</h2>
                <p style="color:rgba(255,255,255,0.65); font-size:15px; line-height:1.7; margin-bottom:32px;">
                    RCI hadir untuk menjembatani kesenjangan akses hukum di Indonesia. Dengan teknologi AI dan jaringan profesional hukum yang terverifikasi, kami memastikan setiap warga negara mendapat pendampingan hukum berkualitas.
                </p>
                <div class="btn-cta-group" style="display:flex; gap:16px; flex-wrap:wrap;">
                    <a href="/register" class="btn-primary" style="padding:16px 32px; font-size:16px;">Bergabung Sekarang</a>
                    <a href="/login" class="btn-ghost" style="color:var(--color-chalk);">Sudah punya akun? →</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
// Animate elements on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(el => {
        if (el.isIntersecting) {
            el.target.style.opacity = '1';
            el.target.classList.add('fade-in-up');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-in-up').forEach(el => {
    el.style.opacity = '0';
    observer.observe(el);
});

// Hero elements animate immediately
setTimeout(() => {
    document.getElementById('hero-text').style.opacity = '1';
    document.getElementById('hero-visual').style.opacity = '1';
}, 100);
</script>
@endpush
