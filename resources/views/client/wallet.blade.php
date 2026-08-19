@extends('layouts.dashboard')
@section('title', 'Dompet — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item">
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
    <a href="/client/wallet" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')
<div style="margin-bottom:32px;">
    <h1 class="font-display text-heading-lg">DOMPET <span style="color:var(--color-ember);">VIRTUAL</span></h1>
    <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Kelola saldo dan transaksi Anda.</p>
</div>

<!-- Balance Card -->
<div style="background:var(--color-obsidian); border-radius:var(--radius-cards); padding:48px; margin-bottom:24px; position:relative; overflow:hidden;">
    <div class="halftone-overlay" style="opacity:0.08;"></div>
    <div style="position:relative; z-index:1;">
        <p style="color:rgba(255,255,255,0.5); font-size:14px; margin-bottom:12px;">Saldo Tersedia</p>
        <p class="font-display" style="font-size:clamp(40px,6vw,96px); color:var(--color-chalk); margin-bottom:24px; letter-spacing:0.02em;" id="balance-display">Memuat...</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button onclick="showTopup()" class="btn-primary" style="padding:14px 28px; font-size:15px;">+ Top Up Saldo</button>
            <button onclick="showUpgrade()" class="btn-secondary" style="padding:14px 28px; font-size:15px; color:white; border-color:rgba(255,255,255,0.3);">⚡ Upgrade ke Pro</button>
        </div>
    </div>
</div>

<!-- Topup Modal -->
<div id="topup-modal" style="display:none; position:fixed; inset:0; background:rgba(7,6,7,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width:100%;max-width:440px; margin:16px;">
        <h2 class="font-display text-heading" style="margin-bottom:20px;">TOP UP SALDO</h2>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:20px;">
            @foreach([50000, 100000, 250000, 500000, 1000000, 2000000] as $amt)
            <button onclick="setTopupAmt({{ $amt }})" class="topup-preset" style="padding:12px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; transition:all 0.15s;">
                Rp {{ number_format($amt, 0, ',', '.') }}
            </button>
            @endforeach
        </div>
        <input type="number" id="topup-amount" class="input-field" placeholder="Atau masukkan jumlah lain (Rp)" style="margin-bottom:16px;">
        <div style="display:flex; gap:10px;">
            <button onclick="doTopup()" class="btn-primary" style="flex:1;">Lanjutkan Pembayaran</button>
            <button onclick="document.getElementById('topup-modal').style.display='none'" class="btn-secondary">Batal</button>
        </div>
    </div>
</div>

<!-- Transactions -->
<div class="card">
    <h2 class="font-display text-heading" style="margin-bottom:24px;">RIWAYAT TRANSAKSI</h2>
    <div id="tx-list">
        <div style="text-align:center; padding:40px; color:rgba(7,6,7,0.4);">Memuat...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const token = localStorage.getItem('rci_token');

window.onUserLoaded = function(user) {
    loadWallet();
};

function loadWallet() {
    fetch('/api/v1/rci/wallet', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const bal = d.balance || d.data?.balance || 0;
            document.getElementById('balance-display').textContent = 'Rp ' + Number(bal).toLocaleString('id-ID');
        }).catch(() => { document.getElementById('balance-display').textContent = 'Rp —'; });

    fetch('/api/v1/rci/wallet/transactions', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const txs = d.data || d || [];
            const el = document.getElementById('tx-list');
            if (!txs.length) {
                el.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada transaksi.</div>';
                return;
            }
            el.innerHTML = txs.map(tx => {
                const isCredit = tx.type === 'credit' || (tx.amount > 0);
                return `<div style="display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1.5px dotted var(--color-pumice);">
                    <div style="width:40px;height:40px;border-radius:50%;background:${isCredit?'rgba(82,74,233,0.1)':'rgba(252,80,0,0.1)'};display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">${isCredit?'↓':'↑'}</div>
                    <div style="flex:1;">
                        <p style="font-weight:500;font-size:14px;margin-bottom:2px;">${tx.description || tx.type}</p>
                        <p style="font-size:12px;color:rgba(7,6,7,0.4);">${new Date(tx.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric', hour:'2-digit', minute:'2-digit'})}</p>
                    </div>
                    <p style="font-weight:500; color:${isCredit?'#524ae9':'#fc5000'}; white-space:nowrap;">
                        ${isCredit?'+':'-'} Rp ${Math.abs(tx.amount).toLocaleString('id-ID')}
                    </p>
                </div>`;
            }).join('');
        }).catch(() => {
            document.getElementById('tx-list').innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);">Gagal memuat transaksi.</div>';
        });
}

function showTopup() { document.getElementById('topup-modal').style.display = 'flex'; }

function setTopupAmt(amt) {
    document.getElementById('topup-amount').value = amt;
    document.querySelectorAll('.topup-preset').forEach(b => {
        b.style.borderColor = 'var(--color-pumice)';
        b.style.background = 'var(--color-pumice)';
    });
    event.currentTarget.style.borderColor = 'var(--color-ember)';
    event.currentTarget.style.background = 'rgba(252,80,0,0.08)';
}

async function doTopup() {
    const amt = document.getElementById('topup-amount').value;
    if (!amt || amt < 10000) { alert('Minimum top up Rp 10.000'); return; }
    try {
        const res = await fetch('/api/v1/rci/topup', {
            method:'POST',
            headers: {'Authorization':'Bearer '+token,'Accept':'application/json','Content-Type':'application/json'},
            body: JSON.stringify({amount: parseInt(amt)})
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal');
        document.getElementById('topup-modal').style.display = 'none';
        if (data.invoice_url || data.data?.invoice_url) {
            window.open(data.invoice_url || data.data.invoice_url, '_blank');
        } else {
            showToast('Top up berhasil!');
            loadWallet();
        }
    } catch(e) { showToast(e.message, 'error'); }
}

function showUpgrade() {
    if (!confirm('Upgrade ke RCI Pro untuk konsultasi AI tak terbatas? Anda akan diarahkan ke halaman pembayaran.')) return;
    fetch('/api/v1/rci/upgrade', {
        method:'POST',
        headers: {'Authorization':'Bearer '+token,'Accept':'application/json'}
    }).then(r=>r.json()).then(d => {
        if (d.invoice_url || d.data?.invoice_url) window.open(d.invoice_url || d.data.invoice_url,'_blank');
        else showToast('Upgrade berhasil!');
    }).catch(() => showToast('Gagal memproses upgrade','error'));
}
</script>
@endpush
