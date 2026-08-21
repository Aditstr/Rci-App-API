@extends('layouts.dashboard')
@section('title', 'Kasus Saya — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/client/cases" class="sidebar-nav-item active">
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
    </a>
</nav>
@endsection

@section('content')
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="font-display text-heading-lg">KASUS <span style="color:var(--color-ember);">SAYA</span></h1>
        <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Pantau semua kasus Anda dalam satu tempat.</p>
    </div>
    <a href="/client/cases/new" class="btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Buat Kasus Baru
    </a>
</div>

<!-- Filter tabs -->
<div style="display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap;" id="status-tabs">
    <button onclick="filterStatus('all',this)" class="tag status-tab" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;background:var(--color-ember);color:white;">Semua</button>
    <button onclick="filterStatus('PENDING',this)" class="tag status-tab" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Menunggu</button>
    <button onclick="filterStatus('IN_PROGRESS',this)" class="tag status-tab" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Berlangsung</button>
    <button onclick="filterStatus('COMPLETED',this)" class="tag status-tab" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Selesai</button>
</div>

<div class="card">
    <div id="cases-table">
        <div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Memuat kasus...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');
let allCases = [];
const statusColors = {
    PENDING:     {bg:'#f5f28e', color:'#070607', label:'Menunggu'},
    IN_PROGRESS: {bg:'#524ae9', color:'#fff', label:'Berlangsung'},
    ESCALATED:   {bg:'#fc5000', color:'#fff', label:'Eskalasi'},
    COMPLETED:   {bg:'#070607', color:'#fff', label:'Selesai'},
    CANCELLED:   {bg:'#e2e2df', color:'rgba(7,6,7,0.5)', label:'Dibatalkan'},
    ON_HOLD:     {bg:'#f5f28e', color:'#070607', label:'Ditunda'},
    DISPUTED:    {bg:'#fc5000', color:'#fff', label:'Sengketa'},
};

window.onUserLoaded = function() {
    fetch('/api/v1/cases', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            allCases = d.data || d || [];
            renderCases(allCases);
        }).catch(() => {
            document.getElementById('cases-table').innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Gagal memuat kasus.</div>';
        });
};

function renderCases(cases) {
    const el = document.getElementById('cases-table');
    if (!cases.length) {
        el.innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);font-size:14px;">Tidak ada kasus. <a href="/client/cases/new" style="color:var(--color-ember);">Buat kasus pertama →</a></div>';
        return;
    }
    el.innerHTML = cases.map(c => {
        const s = statusColors[c.status] || {bg:'#e2e2df',color:'#070607',label:c.status};
        return `<div style="display:flex;align-items:center;gap:16px;padding:18px 0;border-bottom:1.5px dotted var(--color-pumice);flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <p style="font-weight:500;font-size:15px;margin-bottom:4px;">${c.title || 'Kasus #'+c.id}</p>
                <p style="font-size:12px;color:rgba(7,6,7,0.45);">${c.case_type?.replace('_',' ') || 'Umum'} · Dibuat ${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
            </div>
            <span class="tag" style="background:${s.bg};color:${s.color};">${s.label}</span>
            <a href="/client/cases/${c.id}" style="color:var(--color-ember);text-decoration:none;font-size:14px;font-weight:500;white-space:nowrap;">Lihat Detail →</a>
        </div>`;
    }).join('');
}

function filterStatus(status, btn) {
    document.querySelectorAll('.status-tab').forEach(b => { b.style.background='var(--color-sulfur)'; b.style.color='var(--color-obsidian)'; });
    btn.style.background = 'var(--color-ember)'; btn.style.color = 'white';
    renderCases(status === 'all' ? allCases : allCases.filter(c => c.status === status));
}
window.filterStatus = filterStatus;
})();
</script>
@endpush
