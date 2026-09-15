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
    <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Kelola saldo dan transaksi Anda. Top-up via transfer manual.</p>
</div>

<!-- Balance Card -->
<div style="background:var(--color-obsidian); border-radius:var(--radius-cards); padding:48px; margin-bottom:24px; position:relative; overflow:hidden;">
    <div class="halftone-overlay" style="opacity:0.08;"></div>
    <div style="position:relative; z-index:1;">
        <p style="color:rgba(255,255,255,0.5); font-size:14px; margin-bottom:12px;">Saldo Tersedia</p>
        <p class="font-display" style="font-size:clamp(40px,6vw,96px); color:var(--color-chalk); margin-bottom:24px; letter-spacing:0.02em;" id="balance-display">Memuat...</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button onclick="showTopup()" class="btn-primary" style="padding:14px 28px; font-size:15px;">+ Top Up Manual</button>
            <button onclick="showUpgrade()" class="btn-secondary" style="padding:14px 28px; font-size:15px; color:white; border-color:rgba(255,255,255,0.3);">⚡ Upgrade ke Pro</button>
        </div>
    </div>
</div>

<!-- Topup Modal — Manual Transfer -->
<div id="topup-modal" style="display:none; position:fixed; inset:0; background:rgba(7,6,7,0.5); z-index:1000; align-items:center; justify-content:center; overflow-y:auto; padding:16px;">
    <div class="card" style="width:100%;max-width:480px; margin:16px; max-height:90vh; overflow-y:auto;">
        <h2 class="font-display text-heading" style="margin-bottom:12px;">TOP UP MANUAL</h2>
        <div id="bank-info" style="background:rgba(82,74,233,0.08); border:1px solid rgba(82,74,233,0.15); border-radius:12px; padding:14px; margin-bottom:16px; font-size:13px; line-height:1.6;">
            <p style="font-weight:600; margin-bottom:4px;">Transfer ke rekening RCI:</p>
            <p id="bank-info-text">Memuat rekening tujuan...</p>
            <p style="color:rgba(7,6,7,0.45); font-size:12px; margin-top:6px;">Transfer dulu, lalu upload bukti di bawah. Verifikasi maks 1x24 jam.</p>
        </div>
        <div class="grid-3-cols" style="margin-bottom:14px;">
            @foreach([50000, 100000, 250000, 500000, 1000000, 2000000] as $amt)
            <button onclick="setTopupAmt({{ $amt }})" class="topup-preset" style="padding:12px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; transition:all 0.15s;">
                Rp {{ number_format($amt, 0, ',', '.') }}
            </button>
            @endforeach
        </div>
        <input type="number" id="topup-amount" class="input-field" placeholder="Jumlah transfer (Rp) min 10.000" style="margin-bottom:10px;">
        <select id="topup-bank-name" class="input-field" style="margin-bottom:10px;">
            <option value="">-- Bank Pengirim --</option>
            <option value="BCA">BCA</option>
            <option value="BRI">BRI</option>
            <option value="BNI">BNI</option>
            <option value="Mandiri">Mandiri</option>
            <option value="BSI">BSI</option>
            <option value="CIMB">CIMB</option>
            <option value="Lainnya">Lainnya</option>
        </select>
        <input type="text" id="topup-sender-name" class="input-field" placeholder="Nama pengirim (sesuai rekening)" style="margin-bottom:10px;">
        <label style="font-size:13px; font-weight:500; margin-bottom:6px; display:block;">Bukti transfer (jpg/png/pdf max 5MB) *</label>
        <input type="file" id="topup-proof" accept=".jpg,.jpeg,.png,.pdf" class="input-field" style="margin-bottom:16px; padding:8px;">
        <div style="display:flex; gap:10px;">
            <button onclick="doTopup()" class="btn-primary" style="flex:1;" id="topup-submit-btn">Kirim Bukti</button>
            <button onclick="document.getElementById('topup-modal').style.display='none'" class="btn-secondary">Batal</button>
        </div>
        <p id="topup-error" style="color:#ef4444; font-size:13px; margin-top:10px; display:none;"></p>
    </div>
</div>

<!-- Pending Manual History -->
<div class="card" style="margin-bottom:24px; display:none;" id="pending-card">
    <h2 class="font-display text-heading" style="margin-bottom:16px;">MENUNGGU VERIFIKASI</h2>
    <div id="pending-list"></div>
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
(function() {
const token = localStorage.getItem('rci_token');

window.onUserLoaded = function(user) {
    loadWallet();
    loadBankInfo();
    loadPendingManual();
};

function loadBankInfo() {
    fetch('/api/v1/settings/bank_destination', { headers: { 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            let val = d.data ?? d.value ?? d;
            if (typeof val === 'string') {
                try { val = JSON.parse(val); } catch(e) { document.getElementById('bank-info-text').textContent = val; return; }
            }
            if (val && val.account_number) {
                document.getElementById('bank-info-text').innerHTML =
                    `<strong>${val.bank_name || ''} ${val.account_number}</strong> a.n. ${val.account_name || ''}<br><span style="color:rgba(7,6,7,0.5)">${val.note || ''}</span>`;
            } else if (val) {
                document.getElementById('bank-info-text').textContent = JSON.stringify(val);
            } else {
                document.getElementById('bank-info-text').textContent = 'Hubungi admin untuk info rekening.';
            }
        }).catch(() => { document.getElementById('bank-info-text').textContent = 'Gagal memuat rekening. Hubungi admin.'; });
}

function loadPendingManual() {
    fetch('/api/v1/rci/topup/manual', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const list = d.data?.data || d.data || [];
            const pending = Array.isArray(list) ? list.filter(p => p.status === 'pending_proof') : [];
            if (!pending.length) { document.getElementById('pending-card').style.display='none'; return; }
            document.getElementById('pending-card').style.display='block';
            document.getElementById('pending-list').innerHTML = pending.map(p =>
                `<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f0f0f0; font-size:14px;">
                    <span>Rp ${Number(p.amount).toLocaleString('id-ID')} — ${p.bank_name} (${p.sender_name})</span>
                    <span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:20px; font-size:12px;">Menunggu</span>
                </div>`
            ).join('');
        }).catch(()=>{});
}

function loadWallet() {
    fetch('/api/v1/rci/wallet', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const bal = d.balance || d.data?.balance || 0;
            document.getElementById('balance-display').textContent = 'Rp ' + Number(bal).toLocaleString('id-ID');
        }).catch(() => { document.getElementById('balance-display').textContent = 'Rp —'; });

    fetch('/api/v1/rci/wallet/transactions', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            let txs = (d.data && Array.isArray(d.data.data)) ? d.data.data : (d.data || d || []);
            if (!Array.isArray(txs)) txs = [];
            const el = document.getElementById('tx-list');
            if (!txs || !txs.length) {
                el.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada transaksi.</div>';
                return;
            }
            el.innerHTML = txs.map(tx => {
                const isCredit = tx.type === 'credit' || tx.type === 'deposit' || tx.type === 'payment_release' || tx.type === 'refund' || (tx.amount > 0 && tx.type !== 'withdrawal' && tx.type !== 'escrow_hold');
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

function showTopup() { document.getElementById('topup-modal').style.display = 'flex'; document.getElementById('topup-error').style.display='none'; }

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
    const bank = document.getElementById('topup-bank-name').value;
    const sender = document.getElementById('topup-sender-name').value.trim();
    const proof = document.getElementById('topup-proof').files[0];
    const errEl = document.getElementById('topup-error');
    errEl.style.display='none';
    if (!amt || amt < 10000) { errEl.textContent='Minimum top up Rp 10.000'; errEl.style.display='block'; return; }
    if (!bank) { errEl.textContent='Pilih bank pengirim'; errEl.style.display='block'; return; }
    if (!sender) { errEl.textContent='Isi nama pengirim'; errEl.style.display='block'; return; }
    if (!proof) { errEl.textContent='Upload bukti transfer (jpg/png/pdf)'; errEl.style.display='block'; return; }
    const btn = document.getElementById('topup-submit-btn');
    btn.disabled=true; btn.textContent='Mengirim...';
    try {
        const fd = new FormData();
        fd.append('amount', amt);
        fd.append('bank_name', bank);
        fd.append('sender_name', sender);
        fd.append('proof', proof);
        const res = await fetch('/api/v1/rci/topup/manual', {
            method:'POST',
            headers: {'Authorization':'Bearer '+token,'Accept':'application/json'},
            body: fd
        });
        const data = await res.json();
        if (!res.ok) {
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Gagal');
            throw new Error(msg);
        }
        document.getElementById('topup-modal').style.display = 'none';
        showToast(data.message || 'Bukti berhasil dikirim, menunggu verifikasi admin!');
        document.getElementById('topup-amount').value='';
        document.getElementById('topup-proof').value='';
        loadPendingManual();
    } catch(e) { errEl.textContent=e.message; errEl.style.display='block'; }
    finally { btn.disabled=false; btn.textContent='Kirim Bukti'; }
}

function showUpgrade() {
    if (!confirm('Upgrade ke RCI Pro untuk konsultasi AI tak terbatas? Saldo akan dipotong Rp 50.000.')) return;
    fetch('/api/v1/rci/upgrade', {
        method:'POST',
        headers: {'Authorization':'Bearer '+token,'Accept':'application/json'}
    }).then(r=>r.json()).then(d => {
        if (d.success) { showToast('Upgrade berhasil!'); loadWallet(); }
        else showToast(d.message || 'Gagal','error');
    }).catch(() => showToast('Gagal memproses upgrade','error'));
}
window.showTopup   = showTopup;
window.showUpgrade = showUpgrade;
window.setTopupAmt = setTopupAmt;
window.doTopup     = doTopup;
})();
</script>
@endpush
