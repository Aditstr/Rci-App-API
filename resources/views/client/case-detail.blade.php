@extends('layouts.dashboard')
@section('title', 'Detail Kasus — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard</a>
    <a href="/client/cases" class="sidebar-nav-item active"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Kasus Saya</a>
    <a href="/client/ai-chat" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Konsultasi AI</a>
    <a href="/client/wallet" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Dompet</a>
</nav>
@endsection

@section('content')
<div style="margin-bottom:24px;">
    <a href="/client/cases" style="color:rgba(7,6,7,0.45);text-decoration:none;font-size:14px;">← Kembali ke Kasus Saya</a>
</div>

<div id="case-detail-loading" style="text-align:center;padding:80px;color:rgba(7,6,7,0.4);">Memuat detail kasus...</div>

<div id="case-detail-content" style="display:none;">
    <div class="grid-2-cols-aside" style="align-items:flex-start;">
        <!-- Main Info -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
                    <h1 class="font-display text-heading-lg" id="case-title-display" style="font-size:clamp(28px,4vw,56px);">—</h1>
                    <span class="tag" id="case-status-badge">—</span>
                </div>
                <p style="font-size:13px;color:rgba(7,6,7,0.45);margin-bottom:16px;" id="case-meta">—</p>
                <hr class="dotted-divider-h">
                <p style="font-size:15px;line-height:1.7;color:rgba(7,6,7,0.75);" id="case-desc-display">—</p>
            </div>

            <!-- Chat -->
            <div class="chat-container" style="height:400px;">
                <div style="padding:16px 24px;border-bottom:1.5px dotted var(--color-pumice);">
                    <h3 class="font-display" style="font-size:20px;letter-spacing:0.02em;">OBROLAN KASUS</h3>
                </div>
                <div class="chat-messages" id="case-messages"></div>
                <div class="chat-input-bar">
                    <input type="text" id="msg-input" placeholder="Kirim pesan ke paralegal..." style="flex:1;padding:12px 20px;border-radius:100px;border:1.5px solid var(--color-pumice);background:var(--color-pumice);font-family:var(--font-dm-sans);font-weight:500;font-size:14px;outline:none;" onkeydown="if(event.key==='Enter')sendMsg()">
                    <button onclick="sendMsg()" class="btn-primary" style="padding:12px 20px;font-size:14px;">Kirim</button>
                </div>
            </div>
        </div>

        <!-- Actions panel -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <h3 class="font-display" style="font-size:20px;margin-bottom:16px;letter-spacing:0.02em;">TINDAKAN</h3>
                <div style="display:flex;flex-direction:column;gap:10px;" id="case-actions">
                    <div style="text-align:center;padding:20px;color:rgba(7,6,7,0.4);font-size:13px;">Memuat...</div>
                </div>
            </div>

            <!-- Quotation -->
            <div class="card" id="quotation-card" style="display:none;">
                <h3 class="font-display" style="font-size:20px;margin-bottom:12px;letter-spacing:0.02em;">PENAWARAN HARGA</h3>
                <p class="font-display" style="font-size:clamp(24px,3vw,48px);color:var(--color-ember);margin-bottom:12px;" id="quotation-amount">—</p>
                <p style="font-size:13px;color:rgba(7,6,7,0.55);margin-bottom:16px;" id="quotation-notes">—</p>
                <div style="display:flex;gap:8px;">
                    <button onclick="approveQuote()" class="btn-primary" style="flex:1;padding:12px;">Setujui</button>
                    <button onclick="rejectQuote()" class="btn-secondary" style="flex:1;padding:12px;">Tolak</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');
const caseId = window.location.pathname.split('/').pop();

window.onUserLoaded = function() { loadCase(); };

function loadCase() {
    fetch('/api/v1/cases/'+caseId, { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => { if(!r.ok) throw new Error(); return r.json(); })
        .then(d => {
            const c = d.data || d;
            document.getElementById('case-detail-loading').style.display = 'none';
            document.getElementById('case-detail-content').style.display = 'block';

            document.getElementById('case-title-display').textContent = c.title || 'Kasus #'+c.id;
            document.getElementById('case-desc-display').textContent = c.description || '—';
            document.getElementById('case-meta').textContent = `${(c.case_type||'umum').replace('_',' ')} · Dibuat ${new Date(c.created_at).toLocaleDateString('id-ID')}`;

            const statusMap = {PENDING:{bg:'#f5f28e',color:'#070607',label:'Menunggu'},IN_PROGRESS:{bg:'#524ae9',color:'#fff',label:'Berlangsung'},ESCALATED:{bg:'#fc5000',color:'#fff',label:'Eskalasi'},COMPLETED:{bg:'#070607',color:'#fff',label:'Selesai'},CANCELLED:{bg:'#e2e2df',color:'rgba(7,6,7,0.5)',label:'Dibatalkan'}};
            const s = statusMap[c.status] || {bg:'#e2e2df',color:'#070607',label:c.status};
            const badge = document.getElementById('case-status-badge');
            badge.textContent = s.label; badge.style.background = s.bg; badge.style.color = s.color;

            renderActions(c);

            if (c.quotation) {
                document.getElementById('quotation-card').style.display = 'block';
                document.getElementById('quotation-amount').textContent = 'Rp ' + Number(c.quotation.amount).toLocaleString('id-ID');
                document.getElementById('quotation-notes').textContent = c.quotation.notes || '';
            }

            loadMessages();
        }).catch(() => {
            document.getElementById('case-detail-loading').textContent = 'Kasus tidak ditemukan.';
        });
}

function renderActions(c) {
    const el = document.getElementById('case-actions');
    const acts = [];
    if (['PENDING','IN_PROGRESS','ESCALATED'].includes(c.status)) {
        acts.push(`<button onclick="confirmComplete()" class="btn-primary" style="width:100%;padding:12px;">✓ Konfirmasi Selesai</button>`);
        acts.push(`<button onclick="disputeCase()" class="btn-secondary" style="width:100%;padding:12px;">⚠ Ajukan Sengketa</button>`);
        acts.push(`<button onclick="cancelCase()" class="btn-ghost" style="width:100%;padding:12px;color:rgba(7,6,7,0.4);">✕ Batalkan Kasus</button>`);
    } else {
        acts.push(`<p style="text-align:center;font-size:13px;color:rgba(7,6,7,0.4);">Tidak ada tindakan tersedia.</p>`);
    }
    el.innerHTML = acts.join('');
}

function loadMessages() {
    fetch('/api/v1/cases/'+caseId+'/messages', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            const msgs = d.data || d || [];
            const el = document.getElementById('case-messages');
            if (!msgs.length) { el.innerHTML = '<div style="text-align:center;padding:24px;color:rgba(7,6,7,0.4);font-size:13px;">Belum ada pesan.</div>'; return; }
            el.innerHTML = msgs.map(m => {
                const isMe = m.sender_role === 'client';
                return `<div class="chat-message ${isMe?'chat-message-user':'chat-message-ai'}">
                    <p style="font-size:14px;line-height:1.5;">${m.message}</p>
                    <p style="font-size:11px;margin-top:4px;opacity:0.5;">${new Date(m.created_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})}</p>
                </div>`;
            }).join('');
            el.scrollTop = el.scrollHeight;
        });
}

async function sendMsg() {
    const input = document.getElementById('msg-input');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    await fetch('/api/v1/cases/'+caseId+'/messages', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    });
    loadMessages();
}

async function confirmComplete() { await caseAction('confirm-completion'); showToast('Kasus dikonfirmasi selesai!'); }
async function disputeCase()    { await caseAction('dispute'); showToast('Sengketa diajukan.'); }
async function cancelCase()     { if(!confirm('Yakin batalkan kasus?')) return; await caseAction('cancel'); showToast('Kasus dibatalkan.'); }
async function approveQuote()   { await caseAction('quotation/approve'); showToast('Penawaran disetujui!'); document.getElementById('quotation-card').style.display='none'; }
async function rejectQuote()    { await caseAction('quotation/reject'); showToast('Penawaran ditolak.'); document.getElementById('quotation-card').style.display='none'; }

async function caseAction(action) {
    await fetch(`/api/v1/cases/${caseId}/${action}`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }
    });
    loadCase();
}
window.sendMsg = sendMsg;
window.confirmComplete = confirmComplete;
window.disputeCase     = disputeCase;
window.cancelCase      = cancelCase;
window.approveQuote    = approveQuote;
window.rejectQuote     = rejectQuote;
})();
</script>
@endpush
