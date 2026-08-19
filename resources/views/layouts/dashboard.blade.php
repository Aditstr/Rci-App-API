<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — RCI')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/css/caldera.css">
    @stack('head')
</head>
<body>
<!-- ── Mobile Backdrop & Topbar ── -->
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleMobileSidebar()"></div>

<div class="dashboard-layout">

    <!-- ── Sidebar ── -->
    <aside class="sidebar" id="sidebar">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: var(--spacing-32);">
            <a href="/" class="sidebar-logo" style="margin-bottom:0;">
                <div class="nav-logo-icon">R</div>
                <span class="font-display" style="font-size:20px; color:var(--color-obsidian); letter-spacing:0.02em;">RCI</span>
            </a>
            <button class="sidebar-toggle show-mobile" onclick="toggleMobileSidebar()" aria-label="Tutup menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        @yield('sidebar-nav')

        <!-- Profile at bottom -->
        <div style="margin-top:auto; padding-top:24px; border-top:1.5px dotted var(--color-pumice);">
            <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:20px; background:var(--color-pumice);">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--color-ember);display:flex;align-items:center;justify-content:center;color:var(--color-chalk);font-family:var(--font-display);font-size:16px;flex-shrink:0;" id="sidebar-avatar">U</div>
                <div style="flex:1;min-width:0;">
                    <p style="font-weight:500;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="sidebar-name">Pengguna</p>
                    <p style="font-size:12px;color:rgba(7,6,7,0.5);" id="sidebar-role">—</p>
                </div>
            </div>
            <a href="#" onclick="doLogout()" class="sidebar-nav-item" style="margin-top:8px; color:var(--color-ember);">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Keluar
            </a>
        </div>
    </aside>

    <!-- ── Main Content ── -->
    <main class="dashboard-main">
        <!-- Mobile Topbar -->
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" onclick="toggleMobileSidebar()" aria-label="Buka menu">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <a href="/" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
                <div class="nav-logo-icon" style="width:32px;height:32px;font-size:16px;">R</div>
                <span class="font-display" style="font-size:18px; color:var(--color-obsidian);">RCI</span>
            </a>
            <div style="width:40px;"></div> <!-- spacer -->
        </div>

        @yield('content')
    </main>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

@stack('scripts')
<script>
    // Load user info
    const token = localStorage.getItem('rci_token');
    if (!token) { window.location.href = '/login'; }

    fetch('/api/v1/auth/me', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    }).then(r => {
        if (!r.ok) { localStorage.removeItem('rci_token'); window.location.href = '/login'; }
        return r.json();
    }).then(d => {
        const u = d.user || d;
        document.getElementById('sidebar-name').textContent = u.name || 'Pengguna';
        document.getElementById('sidebar-role').textContent = u.role || '—';
        document.getElementById('sidebar-avatar').textContent = (u.name || 'U')[0].toUpperCase();
        if (window.onUserLoaded) window.onUserLoaded(u);
    }).catch(() => { window.location.href = '/login'; });

    function doLogout() {
        fetch('/api/v1/auth/logout', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).finally(() => { localStorage.removeItem('rci_token'); window.location.href = '/login'; });
    }

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('visible');
    }
    window.toggleMobileSidebar = toggleMobileSidebar;

    function showToast(msg, type = 'info') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.backgroundColor = type === 'error' ? '#fc5000' : '#070607';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }
    window.showToast = showToast;
</script>
</body>
</html>
