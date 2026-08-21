@extends('layouts.dashboard')
@section('title', 'Revenue Royalti — RCI Lawyer')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/lawyer" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/lawyer/cases" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Eskalasi
    </a>
    <a href="/lawyer/revenue" class="sidebar-nav-item active">
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
<div style="margin-bottom:32px;">
    <span class="tag" style="background:var(--color-obsidian); color:var(--color-chalk); margin-bottom:8px;">⚖️ Pengacara</span>
    <h1 class="font-display text-heading-lg">REVENUE <span style="color:var(--color-ember);">ROYALTI</span></h1>
    <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Riwayat pendapatan profesional Anda dari platform RCI.</p>
</div>

<!-- Revenue Summary Cards -->
<div class="stat-grid" style="margin-bottom:32px;">
    <div style="background:var(--color-obsidian); border-radius:var(--radius-cards); padding:32px; position:relative; overflow:hidden;">
        <div class="halftone-overlay" style="opacity:0.08;"></div>
        <p style="color:rgba(255,255,255,0.5); font-size:13px; margin-bottom:8px;">Total Revenue</p>
        <p class="font-display" style="font-size:clamp(28px,4vw,56px); color:var(--color-chalk);" id="r-total">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Bulan Ini</p>
        <p class="stat-card-value" style="font-size:clamp(22px,3vw,48px);" id="r-monthly">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Kasus Selesai</p>
        <p class="stat-card-value" id="r-cases">—</p>
    </div>
    <div class="card" style="background:var(--color-plasma-violet); position:relative; overflow:hidden;">
        <div class="halftone-overlay" style="opacity:0.15;"></div>
        <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Rating Rata-rata</p>
        <p class="stat-card-value" id="r-rating">—</p>
    </div>
</div>

<!-- Transaction History -->
<div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <h2 class="font-display text-heading">RIWAYAT PEMBAYARAN</h2>
        <span class="tag" id="tx-count">0 transaksi</span>
    </div>
    <div id="tx-list">
        <div style="text-align:center; padding:60px; color:rgba(7,6,7,0.4);">Memuat riwayat...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    window.onUserLoaded = function() {
        const token = localStorage.getItem('rci_token');

        // Load stats
        fetch('/api/v1/lawyer/dashboard/stats', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            const s = d.data || d;
            document.getElementById('r-total').textContent   = s.total_revenue ? 'Rp ' + Number(s.total_revenue).toLocaleString('id-ID') : 'Rp 0';
            document.getElementById('r-monthly').textContent = s.monthly_revenue ? 'Rp ' + Number(s.monthly_revenue).toLocaleString('id-ID') : 'Rp 0';
            document.getElementById('r-cases').textContent   = s.completed_cases ?? '0';
            document.getElementById('r-rating').textContent  = s.average_rating ? Number(s.average_rating).toFixed(1) + ' ★' : '—';
        }).catch(() => {});

        // Load wallet transactions
        fetch('/api/v1/rci/wallet/transactions', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            const txs = (d.data || d || []).filter(tx => ['payment_release', 'admin_fee', 'deposit'].includes(tx.type));
            const el = document.getElementById('tx-list');
            document.getElementById('tx-count').textContent = txs.length + ' transaksi';

            if (!txs.length) {
                el.innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada riwayat pembayaran.</div>';
                return;
            }

            const typeLabels = { payment_release: 'Royalti Kasus', deposit: 'Top Up', admin_fee: 'Biaya Platform', withdrawal: 'Penarikan' };
            el.innerHTML = txs.map(tx => {
                const isCredit = ['payment_release', 'deposit'].includes(tx.type);
                const label = typeLabels[tx.type] || tx.type;
                return `<div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1.5px dotted var(--color-pumice);">
                    <div style="width:44px;height:44px;border-radius:50%;background:${isCredit?'rgba(82,74,233,0.1)':'rgba(252,80,0,0.1)'};display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                        ${isCredit?'↓':'↑'}
                    </div>
                    <div style="flex:1;">
                        <p style="font-weight:500;font-size:15px;margin-bottom:2px;">${label}</p>
                        <p style="font-size:12px;color:rgba(7,6,7,0.4);">${tx.description || '—'}</p>
                        <p style="font-size:11px;color:rgba(7,6,7,0.35);margin-top:2px;">${new Date(tx.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'})}</p>
                    </div>
                    <p style="font-weight:600;font-size:16px;color:${isCredit?'#524ae9':'#fc5000'};white-space:nowrap;">
                        ${isCredit?'+':'-'} Rp ${Number(Math.abs(tx.amount)).toLocaleString('id-ID')}
                    </p>
                </div>`;
            }).join('');
        }).catch(() => {
            document.getElementById('tx-list').innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Gagal memuat riwayat.</div>';
        });
    };
})();
</script>
@endpush
