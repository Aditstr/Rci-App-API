<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'RCI — Roys Counsel Indonesia. Platform konsultasi hukum cerdas dengan AI, Paralegal, dan Pengacara profesional.')">
    <title>@yield('title', 'RCI — Roys Counsel Indonesia')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- CSS: static file committed to git, always available in production --}}
    <link rel="stylesheet" href="/css/caldera.css">
    @stack('head')
</head>
<body>

<!-- ── Navigation ── -->
<nav class="nav-bar">
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <div class="nav-logo-icon">R</div>
            <span class="nav-logo-text">RCI</span>
        </a>

        <ul class="nav-links" id="nav-links">
            <li><a href="/#layanan" class="nav-link">Layanan</a></li>
            <div class="nav-divider"></div>
            <li><a href="/#cara-kerja" class="nav-link">Cara Kerja</a></li>
            <div class="nav-divider"></div>
            <li><a href="/#tentang" class="nav-link">Tentang</a></li>
        </ul>

        <div class="nav-actions">
            <a href="/login" class="btn-ghost">Masuk</a>
            <a href="/register" class="btn-primary">Mulai Gratis</a>
        </div>

        <button class="mobile-nav-toggle" onclick="document.getElementById('nav-links').classList.toggle('open')" aria-label="Toggle menu">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
</nav>

<!-- ── Page Content ── -->
@yield('content')

<!-- ── Footer ── -->
<footer style="background-color: var(--color-obsidian); color: var(--color-chalk); padding: 64px 0 32px;">
    <div class="page-container">
        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap: 48px; margin-bottom: 48px;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                    <div class="nav-logo-icon">R</div>
                    <span class="font-display" style="font-size:24px; color:var(--color-chalk); letter-spacing:0.02em;">RCI</span>
                </div>
                <p style="color:rgba(255,255,255,0.6); font-size:14px; max-width:280px; line-height:1.6;">
                    Roys Counsel Indonesia — ekosistem konsultasi hukum cerdas dengan dukungan AI, Paralegal, dan Pengacara profesional.
                </p>
            </div>
            <div>
                <p class="font-display" style="font-size:18px; color:var(--color-chalk); margin-bottom:16px; letter-spacing:0.02em;">LAYANAN</p>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:10px;">
                    <li><a href="/#layanan" style="color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; transition:color 0.15s;" onmouseover="this.style.color='#fc5000'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">Konsultasi AI</a></li>
                    <li><a href="/#layanan" style="color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; transition:color 0.15s;" onmouseover="this.style.color='#fc5000'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">Bantuan Paralegal</a></li>
                    <li><a href="/#layanan" style="color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; transition:color 0.15s;" onmouseover="this.style.color='#fc5000'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">Pengacara Profesional</a></li>
                </ul>
            </div>
            <div>
                <p class="font-display" style="font-size:18px; color:var(--color-chalk); margin-bottom:16px; letter-spacing:0.02em;">PERUSAHAAN</p>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:10px;">
                    <li><a href="/#tentang" style="color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; transition:color 0.15s;" onmouseover="this.style.color='#fc5000'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">Tentang Kami</a></li>
                    <li><a href="/api-docs" style="color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; transition:color 0.15s;" onmouseover="this.style.color='#fc5000'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">Dokumentasi API</a></li>
                </ul>
            </div>
        </div>
        <hr style="border:none; border-top:1.5px dotted rgba(255,255,255,0.15); margin-bottom:24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <p style="color:rgba(255,255,255,0.4); font-size:12px;">© {{ date('Y') }} Roys Counsel Indonesia. Hak cipta dilindungi.</p>
            <p style="color:rgba(255,255,255,0.4); font-size:12px;">Dibuat dengan Caldera Design System</p>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
