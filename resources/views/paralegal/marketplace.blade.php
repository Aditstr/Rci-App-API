@extends('layouts.dashboard')
@section('title', 'Job Marketplace — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/paralegal" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/paralegal/kanban" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Kanban Board
    </a>
    <a href="/paralegal/marketplace" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Job Marketplace
    </a>
    <a href="/paralegal/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="font-display text-heading-lg">JOB <span style="color:var(--color-ember);">MARKETPLACE</span></h1>
        <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Kasus publik yang tersedia untuk diambil.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;" id="filter-buttons">
        <button onclick="filterCases('all', this)" class="tag active-filter" style="cursor:pointer; border:none; padding:8px 16px; font-size:14px; background:var(--color-ember); color:white;">Semua</button>
        <button onclick="filterCases('perdata', this)" class="tag" style="cursor:pointer; border:none; padding:8px 16px; font-size:14px;">Perdata</button>
        <button onclick="filterCases('pidana', this)" class="tag" style="cursor:pointer; border:none; padding:8px 16px; font-size:14px;">Pidana</button>
        <button onclick="filterCases('tata_usaha', this)" class="tag" style="cursor:pointer; border:none; padding:8px 16px; font-size:14px;">Tata Usaha</button>
    </div>
</div>

<div class="card-grid" id="marketplace-grid">
    <div style="grid-column:1/-1; text-align:center; padding:60px; color:rgba(7,6,7,0.4);">Memuat kasus tersedia...</div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');
let allCases = [];
let currentFilter = 'all';

window.onUserLoaded = function() { loadMarketplace(); };

function loadMarketplace() {
    fetch('/api/v1/paralegal/marketplace', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            let items = d.data;
            if (items && Array.isArray(items.data)) items = items.data;
            allCases = items || d || [];
            renderCases(allCases);
        }).catch(() => {
            document.getElementById('marketplace-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Gagal memuat data marketplace.</div>';
        });
}

function renderCases(cases) {
    const grid = document.getElementById('marketplace-grid');
    if (!cases.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:rgba(7,6,7,0.4);font-size:14px;">Tidak ada kasus yang tersedia saat ini.</div>';
        return;
    }
    grid.innerHTML = cases.map(c => {
        const typeColors = { perdata:'var(--color-sulfur)', pidana:'var(--color-ember)', tata_usaha:'var(--color-plasma-violet)' };
        const tagBg = typeColors[c.category] || 'var(--color-pumice)';
        const tagColor = c.category === 'perdata' || c.category === 'general' ? 'var(--color-obsidian)' : 'var(--color-chalk)';
        return `<div class="card" style="display:flex;flex-direction:column;gap:16px;">
            <div style="height:8px;border-radius:8px;background:${tagBg};width:48px;"></div>
            <div>
                <span class="tag" style="background:${tagBg};color:${tagColor};margin-bottom:10px;">${(c.category||'umum').replace('_',' ')}</span>
                <h3 class="font-display text-heading" style="font-size:24px;margin-bottom:8px;letter-spacing:0.02em;line-height:1.2;">${c.title || 'Kasus Hukum #'+c.id}</h3>
                <p style="font-size:13px;color:rgba(7,6,7,0.55);line-height:1.6;">${(c.description||'').substring(0,120)}${(c.description||'').length>120?'...':''}</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-top:auto;">
                <span style="font-size:12px;color:rgba(7,6,7,0.4);">${new Date(c.created_at).toLocaleDateString('id-ID')}</span>
                <span style="flex:1;"></span>
                <button onclick="applyCase(${c.id}, this)" class="btn-primary" style="padding:10px 20px;font-size:14px;">Ambil Kasus</button>
            </div>
        </div>`;
    }).join('');
}

function filterCases(type, btn) {
    currentFilter = type;
    document.querySelectorAll('#filter-buttons .tag').forEach(b => {
        b.style.background = 'var(--color-sulfur)';
        b.style.color = 'var(--color-obsidian)';
    });
    btn.style.background = 'var(--color-ember)';
    btn.style.color = 'white';
    const filtered = type === 'all' ? allCases : allCases.filter(c => {
        // Map UI types to database categories
        const typeMap = { 'perdata': 'general', 'pidana': 'criminal', 'tata_usaha': 'corporate' };
        return c.category === (typeMap[type] || type);
    });
    renderCases(filtered);
}

function applyCase(caseId, btn) {
    if (!confirm('Ambil kasus ini?')) return;
    btn.textContent = '...';
    btn.disabled = true;
    fetch(`/api/v1/paralegal/marketplace/${caseId}/apply`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }
    }).then(r => {
        if (!r.ok) throw new Error('Gagal mengambil kasus');
        showToast('Kasus berhasil diambil! Cek Kanban Board Anda.');
        btn.textContent = '✓ Diambil';
        btn.style.background = 'var(--color-obsidian)';
    }).catch(err => {
        showToast(err.message, 'error');
        btn.textContent = 'Ambil Kasus';
        btn.disabled = false;
    });
}
window.filterCases = filterCases;
window.applyCase   = applyCase;
})();
</script>
@endpush
