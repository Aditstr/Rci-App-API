@extends('layouts.dashboard')
@section('title', 'Kasus Eskalasi — RCI Lawyer')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/lawyer" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/lawyer/cases" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Eskalasi
    </a>
    <a href="/lawyer/revenue" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Revenue Royalti
    </a>
    <a href="/lawyer/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <span class="tag" style="background:var(--color-obsidian); color:var(--color-chalk); margin-bottom:8px;">⚖️ Pengacara</span>
        <h1 class="font-display text-heading-lg">KASUS <span style="color:var(--color-ember);">ESKALASI</span></h1>
        <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Kasus yang dieskalasi dari Paralegal dan menunggu penanganan Anda.</p>
    </div>
    <span class="tag" id="cases-count" style="background:var(--color-ember); color:white; font-size:16px; padding:10px 20px;">0 Kasus</span>
</div>

<!-- Filter tabs -->
<div style="display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap;" id="filter-tabs">
    <button onclick="filterCases('all', this)" class="tag" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;background:var(--color-ember);color:white;">Semua</button>
    <button onclick="filterCases('ESCALATED', this)" class="tag" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Eskalasi</button>
    <button onclick="filterCases('active', this)" class="tag" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Aktif</button>
    <button onclick="filterCases('completed', this)" class="tag" style="cursor:pointer;border:none;padding:8px 16px;font-size:14px;">Selesai</button>
</div>

<div class="card">
    <div id="cases-list">
        <div style="text-align:center; padding:60px; color:rgba(7,6,7,0.4);">Memuat kasus...</div>
    </div>
</div>

<!-- Quote Modal -->
<div id="quote-modal" style="display:none; position:fixed; inset:0; background:rgba(7,6,7,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width:100%; max-width:480px; margin:16px;">
        <h2 class="font-display text-heading" style="margin-bottom:8px;">BERI PENAWARAN</h2>
        <p style="font-size:13px; color:rgba(7,6,7,0.5); margin-bottom:20px;" id="quote-case-title">—</p>
        <div style="margin-bottom:16px;">
            <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Biaya Layanan (Rp)</label>
            <input type="number" id="quote-amount" class="input-field" placeholder="Misal: 500000" min="50000">
        </div>
        <div style="margin-bottom:20px;">
            <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Catatan Penawaran</label>
            <textarea id="quote-notes" class="input-field" rows="3" placeholder="Jelaskan lingkup pekerjaan..."></textarea>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="submitQuote()" class="btn-primary" style="flex:1;">Kirim Penawaran</button>
            <button onclick="closeQuoteModal()" class="btn-secondary">Batal</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    let allCases = [];
    let quotingCaseId = null;

    window.onUserLoaded = function() { loadCases(); };

    function loadCases() {
        const token = localStorage.getItem('rci_token');
        fetch('/api/v1/expert/cases', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            allCases = d.data || d || [];
            document.getElementById('cases-count').textContent = allCases.length + ' Kasus';
            renderCases(allCases);
        }).catch(() => {
            document.getElementById('cases-list').innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Gagal memuat kasus.</div>';
        });
    }

    function renderCases(cases) {
        const el = document.getElementById('cases-list');
        if (!cases.length) {
            el.innerHTML = '<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada kasus yang dieskalasi.</div>';
            return;
        }
        const statusColors = {
            ESCALATED: {bg:'#524ae9', color:'#fff', label:'Eskalasi'},
            active:    {bg:'#22c55e', color:'#fff', label:'Aktif'},
            completed: {bg:'#070607', color:'#fff', label:'Selesai'},
        };
        el.innerHTML = cases.map(c => {
            const s = statusColors[c.status] || {bg:'#e2e2df', color:'#070607', label: c.status};
            return `<div style="display:flex;align-items:center;gap:16px;padding:20px 0;border-bottom:1.5px dotted var(--color-pumice);flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <p style="font-weight:600;font-size:15px;margin-bottom:4px;">${c.title || 'Kasus #' + c.id}</p>
                    <p style="font-size:12px;color:rgba(7,6,7,0.45);">Tipe: ${(c.case_type||'umum').replace('_',' ')} · ${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
                    ${c.description ? `<p style="font-size:13px;color:rgba(7,6,7,0.6);margin-top:6px;line-height:1.5;">${c.description.substring(0,100)}...</p>` : ''}
                </div>
                <span class="tag" style="background:${s.bg};color:${s.color};">${s.label}</span>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <button onclick="window.openQuoteModal(${c.id}, '${(c.title||'Kasus #'+c.id).replace(/'/g,"\\'")}', this)" class="btn-primary" style="padding:10px 18px;font-size:13px;">Beri Penawaran</button>
                </div>
            </div>`;
        }).join('');
    }

    function filterCases(status, btn) {
        document.querySelectorAll('#filter-tabs .tag').forEach(b => { b.style.background = 'var(--color-sulfur)'; b.style.color = 'var(--color-obsidian)'; });
        btn.style.background = 'var(--color-ember)'; btn.style.color = 'white';
        const filtered = status === 'all' ? allCases : allCases.filter(c => c.status === status || c.status?.toLowerCase() === status);
        renderCases(filtered);
    }

    window.openQuoteModal = function(caseId, title) {
        quotingCaseId = caseId;
        document.getElementById('quote-case-title').textContent = title;
        document.getElementById('quote-amount').value = '';
        document.getElementById('quote-notes').value = '';
        document.getElementById('quote-modal').style.display = 'flex';
    };

    window.closeQuoteModal = function() {
        document.getElementById('quote-modal').style.display = 'none';
        quotingCaseId = null;
    };

    window.submitQuote = async function() {
        if (!quotingCaseId) return;
        const token = localStorage.getItem('rci_token');
        const amount = parseInt(document.getElementById('quote-amount').value);
        const notes  = document.getElementById('quote-notes').value.trim();
        if (!amount || amount < 50000) { showToast('Biaya minimum Rp 50.000', 'error'); return; }
        try {
            const res = await fetch(`/api/v1/lawyer/cases/${quotingCaseId}/quote`, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount, notes: notes || 'Penawaran harga layanan hukum profesional.' })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal mengirim penawaran');
            showToast('Penawaran berhasil dikirim ke klien!');
            window.closeQuoteModal();
            loadCases();
        } catch(e) { showToast(e.message, 'error'); }
    };

    window.filterCases = filterCases;
})();
</script>
@endpush
