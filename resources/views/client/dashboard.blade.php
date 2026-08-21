@extends('layouts.dashboard')
@section('title', 'Dashboard Klien — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/client/cases" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Saya
    </a>
    <a href="/client/ai-chat" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Konsultasi AI
    </a>
    <a href="/client/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
    <a href="/client/notifications" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifikasi
        <span id="notif-badge" style="margin-left:auto; background:var(--color-ember); color:white; border-radius:var(--radius-pills); padding:2px 7px; font-size:11px; display:none;"></span>
    </a>
</nav>
@endsection

@section('content')
<div id="client-dashboard">
    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px;">
        <div>
            <p style="font-size:13px; color:rgba(7,6,7,0.45); margin-bottom:4px;" id="greeting-date"></p>
            <h1 class="font-display text-heading-lg">SELAMAT DATANG, <span style="color:var(--color-ember);" id="greeting-name">—</span></h1>
        </div>
        <a href="/client/cases/new" class="btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Buat Kasus Baru
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid" style="margin-bottom:32px;" id="stat-cards">
        <div class="stat-card">
            <p class="stat-card-label">Kasus Aktif</p>
            <p class="stat-card-value" id="stat-active">—</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Kasus Selesai</p>
            <p class="stat-card-value" id="stat-done">—</p>
        </div>
        <div class="card" style="background:var(--color-plasma-violet); overflow:hidden; position:relative;">
            <div class="halftone-overlay" style="opacity:0.15;"></div>
            <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Saldo Dompet</p>
            <p class="stat-card-value" style="font-size:clamp(28px,4vw,64px);" id="stat-wallet">—</p>
        </div>
        <div class="card">
            <p class="stat-card-label" style="color:rgba(7,6,7,0.5);">Paket</p>
            <p class="font-display" style="font-size:clamp(28px,4vw,56px); line-height:1.1;" id="stat-tier">—</p>
        </div>
    </div>

    <!-- Recent Cases -->
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
            <h2 class="font-display text-heading">KASUS TERBARU</h2>
            <a href="/client/cases" class="btn-ghost" style="font-size:14px;">Lihat semua →</a>
        </div>
        <div id="cases-list">
            <div style="text-align:center; padding:40px; color:rgba(7,6,7,0.4);">Memuat...</div>
        </div>
    </div>

    <!-- Quick AI Chat -->
    <div style="background:var(--color-obsidian); border-radius:var(--radius-cards); padding:var(--card-padding); display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
        <div>
            <span class="tag" style="background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.7); margin-bottom:12px;">⚡ Instan</span>
            <h3 class="font-display text-heading" style="color:var(--color-chalk); margin-bottom:8px;">TANYA AI HUKUM KAMI</h3>
            <p style="color:rgba(255,255,255,0.55); font-size:14px;">Dapatkan analisis hukum awal dalam hitungan detik.</p>
        </div>
        <a href="/client/ai-chat" class="btn-primary" style="padding:16px 32px; font-size:16px; flex-shrink:0;">
            Mulai Konsultasi AI →
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Date greeting
const now = new Date();
document.getElementById('greeting-date').textContent = now.toLocaleDateString('id-ID', {weekday:'long', year:'numeric', month:'long', day:'numeric'});

function formatRupiah(n) {
    return 'Rp ' + (n || 0).toLocaleString('id-ID');
}

function caseStatusBadge(rawStatus) {
    let status = (rawStatus || '').toUpperCase();
    if (status === 'SUBMITTED') status = 'PENDING';
    
    const map = {
        PENDING:      {bg:'#f5f28e', color:'#070607', label:'Menunggu'},
        IN_PROGRESS:  {bg:'#524ae9', color:'#fff', label:'Berlangsung'},
        ESCALATED:    {bg:'#fc5000', color:'#fff', label:'Eskalasi'},
        COMPLETED:    {bg:'#070607', color:'#fff', label:'Selesai'},
        CANCELLED:    {bg:'#e2e2df', color:'rgba(7,6,7,0.5)', label:'Dibatalkan'},
        DISPUTED:     {bg:'#fc5000', color:'#fff', label:'Dipersengketakan'},
        ON_HOLD:      {bg:'#f5f28e', color:'#070607', label:'Ditunda'},
    };
    const s = map[status] || {bg:'#e2e2df', color:'#070607', label:rawStatus};
    return `<span class="tag" style="background:${s.bg}; color:${s.color};">${s.label}</span>`;
}

window.onUserLoaded = function(user) {
    document.getElementById('greeting-name').textContent = (user.name || 'Pengguna').split(' ')[0].toUpperCase();
    document.getElementById('stat-tier').textContent = user.subscription_tier === 'pro' ? 'PRO' : 'GRATIS';

    // Wallet
    fetch('/api/v1/rci/wallet', { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('rci_token'), 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            document.getElementById('stat-wallet').textContent = formatRupiah(d.balance || d.data?.balance || 0);
        }).catch(() => {});

    // Cases
    fetch('/api/v1/cases', { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('rci_token'), 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            let cases = (d.data && Array.isArray(d.data.data)) ? d.data.data : (d.data || d || []);
            if (!Array.isArray(cases)) cases = [];
            
            const active = cases.filter(c => {
                let s = (c.status || '').toUpperCase();
                return !['COMPLETED','CANCELLED'].includes(s);
            }).length;
            const done = cases.filter(c => (c.status || '').toUpperCase() === 'COMPLETED').length;
            document.getElementById('stat-active').textContent = active;
            document.getElementById('stat-done').textContent = done;

            const list = document.getElementById('cases-list');
            if (!cases.length) {
                list.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada kasus. <a href="/client/cases/new" style="color:var(--color-ember);">Buat kasus pertama Anda →</a></div>';
                return;
            }
            list.innerHTML = cases.slice(0,5).map(c => `
                <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1.5px dotted var(--color-pumice);" class="case-row">
                    <div style="flex:1;">
                        <p style="font-weight:500;font-size:15px;margin-bottom:4px;">${c.title || c.description?.substring(0,50) || 'Kasus #'+c.id}</p>
                        <p style="font-size:12px;color:rgba(7,6,7,0.45);">${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
                    </div>
                    ${caseStatusBadge(c.status)}
                    <a href="/client/cases/${c.id}" style="font-size:13px;color:var(--color-ember);text-decoration:none;white-space:nowrap;">Detail →</a>
                </div>
            `).join('');
        }).catch(() => {
            document.getElementById('cases-list').innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);">Gagal memuat kasus.</div>';
        });

    // Notif badge
    fetch('/api/v1/notifications/unread-count', { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('rci_token'), 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const n = d.count || d.unread_count || 0;
            if (n > 0) {
                const badge = document.getElementById('notif-badge');
                badge.textContent = n;
                badge.style.display = 'inline';
            }
        }).catch(() => {});
};
</script>
@endpush
