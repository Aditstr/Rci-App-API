@extends('layouts.dashboard')
@section('title', 'Dashboard Pengacara — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/lawyer" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/lawyer/cases" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Eskalasi
    </a>
    <a href="/lawyer/revenue" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Revenue Royalti
    </a>
    <a href="/client/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <span class="tag" style="background:var(--color-obsidian); color:var(--color-chalk); margin-bottom:8px;">⚖️ Pengacara</span>
        <h1 class="font-display text-heading-lg">DASHBOARD<br><span style="color:var(--color-ember);">PENGACARA</span></h1>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:32px;">
    <div class="stat-card">
        <p class="stat-card-label">Kasus Aktif</p>
        <p class="stat-card-value" id="l-active">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Kasus Selesai</p>
        <p class="stat-card-value" id="l-done">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Total Revenue</p>
        <p class="stat-card-value" style="font-size:clamp(22px,3vw,56px);" id="l-revenue">—</p>
    </div>
    <div class="card" style="background:var(--color-plasma-violet); position:relative; overflow:hidden;">
        <div class="halftone-overlay" style="opacity:0.15;"></div>
        <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Rating Klien</p>
        <p class="stat-card-value" id="l-rating">—</p>
    </div>
</div>

<!-- Escalated Cases -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <h2 class="font-display text-heading">KASUS MASUK</h2>
        <span class="tag" id="cases-count" style="background:var(--color-ember); color:white;">0</span>
    </div>
    <div id="lawyer-cases">
        <div style="text-align:center; padding:40px; color:rgba(7,6,7,0.4);">Memuat...</div>
    </div>
</div>

<!-- Revenue Detail -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
    <div class="card">
        <h3 class="font-display" style="font-size:22px; margin-bottom:20px; letter-spacing:0.02em;">PENAWARAN AKTIF</h3>
        <div id="quotation-list">
            <div style="text-align:center; padding:24px; color:rgba(7,6,7,0.4); font-size:14px;">Belum ada penawaran aktif.</div>
        </div>
    </div>
    <div class="card" style="background:var(--color-obsidian);">
        <h3 class="font-display" style="font-size:22px; margin-bottom:8px; letter-spacing:0.02em; color:var(--color-chalk);">REVENUE BULAN INI</h3>
        <p class="font-display" style="font-size:clamp(32px,4vw,72px); color:var(--color-ember); margin-bottom:16px; letter-spacing:0.02em;" id="l-monthly">—</p>
        <p style="font-size:13px; color:rgba(255,255,255,0.45);">Total royalti profesional yang diterima bulan berjalan.</p>
        <a href="/lawyer/revenue" class="btn-primary" style="margin-top:24px; display:inline-flex;">Riwayat Lengkap →</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');

window.onUserLoaded = function(user) {
    // Stats
    fetch('/api/v1/lawyer/dashboard/stats', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        const s = d.data || d;
        document.getElementById('l-active').textContent  = s.active_cases ?? '—';
        document.getElementById('l-done').textContent    = s.completed_cases ?? '—';
        document.getElementById('l-revenue').textContent = s.total_revenue ? 'Rp '+Number(s.total_revenue).toLocaleString('id-ID') : '—';
        document.getElementById('l-rating').textContent  = s.average_rating ? Number(s.average_rating).toFixed(1)+' ★' : '—';
        document.getElementById('l-monthly').textContent = s.monthly_revenue ? 'Rp '+Number(s.monthly_revenue).toLocaleString('id-ID') : 'Rp 0';
    }).catch(() => {});

    // Cases
    fetch('/api/v1/expert/cases', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        const cases = d.data || d || [];
        document.getElementById('cases-count').textContent = cases.length;
        const el = document.getElementById('lawyer-cases');
        if (!cases.length) {
            el.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada kasus yang dieskalasi.</div>';
            return;
        }
        el.innerHTML = cases.map(c => `
            <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1.5px dotted var(--color-pumice);">
                <div style="flex:1;">
                    <p style="font-weight:500;font-size:15px;margin-bottom:4px;">${c.title || 'Kasus #'+c.id}</p>
                    <p style="font-size:12px;color:rgba(7,6,7,0.45);">Tipe: ${c.case_type || '—'} · ${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
                </div>
                <span class="tag" style="background:${c.status==='ESCALATED'?'#524ae9':'#fc5000'};color:white;">${c.status}</span>
                <button onclick="openQuote(${c.id})" class="btn-primary" style="padding:8px 16px;font-size:13px;">Beri Penawaran</button>
            </div>
        `).join('');
    }).catch(() => {
        document.getElementById('lawyer-cases').innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);">Gagal memuat kasus.</div>';
    });
};

function openQuote(caseId) {
    const fee = prompt('Masukkan biaya penawaran (Rp):');
    if (!fee || isNaN(fee)) return;
    fetch(`/api/v1/lawyer/cases/${caseId}/quote`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: parseInt(fee), notes: 'Penawaran harga layanan hukum.' })
    }).then(r => {
        if (!r.ok) throw new Error('Gagal mengirim penawaran');
        showToast('Penawaran berhasil dikirim!');
    }).catch(err => showToast(err.message, 'error'));
}
window.openQuote = openQuote;
})();
</script>
@endpush
