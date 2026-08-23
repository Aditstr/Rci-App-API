@extends('layouts.dashboard')
@section('title', 'Detail Kasus — RCI')

@section('sidebar-nav')
@php($isLawyerWorkspace = request()->is('lawyer/*'))
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="{{ $isLawyerWorkspace ? '/lawyer' : '/paralegal' }}" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ $isLawyerWorkspace ? '/lawyer/cases' : '/paralegal/kanban' }}" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        {{ $isLawyerWorkspace ? 'Kasus Eskalasi' : 'Kanban Board' }}
    </a>
    <a href="{{ $isLawyerWorkspace ? '/lawyer/revenue' : '/paralegal/marketplace' }}" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        {{ $isLawyerWorkspace ? 'Revenue Royalti' : 'Job Marketplace' }}
    </a>
    <a href="{{ $isLawyerWorkspace ? '/lawyer/wallet' : '/paralegal/wallet' }}" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')
<div style="margin-bottom:24px;">
    <a href="{{ $isLawyerWorkspace ? '/lawyer/cases' : '/paralegal' }}" style="color:rgba(7,6,7,0.45);text-decoration:none;font-size:14px;">← Kembali ke Dashboard</a>
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

            @include('partials.payment-safety-notice', ['audience' => 'expert'])

            <!-- Chat -->
            <div class="chat-container" style="height:440px;">
                <div style="padding:16px 24px;border-bottom:1.5px dotted var(--color-pumice);">
                    <h3 class="font-display" style="font-size:20px;letter-spacing:0.02em;">OBROLAN KASUS</h3>
                </div>
                <div class="chat-messages" id="case-messages"></div>
                <div class="chat-input-bar">
                    <input type="text" id="msg-input" placeholder="Kirim pesan ke klien..." style="flex:1;padding:12px 20px;border-radius:100px;border:1.5px solid var(--color-pumice);background:var(--color-pumice);font-family:var(--font-dm-sans);font-weight:500;font-size:14px;outline:none;" onkeydown="if(event.key==='Enter')sendMsg()">
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
let currentUserId = null;
let currentUserRole = null;

window.onUserLoaded = function(user) { currentUserId = user.id; currentUserRole = user.role; loadCase(); };

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
}

function loadCase() {
    fetch('/api/v1/expert/cases/'+caseId, { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => { if(!r.ok) throw new Error(); return r.json(); })
        .then(d => {
            const c = d.data || d;
            document.getElementById('case-detail-loading').style.display = 'none';
            document.getElementById('case-detail-content').style.display = 'block';

            document.getElementById('case-title-display').textContent = c.title || 'Kasus #'+c.id;
            document.getElementById('case-desc-display').textContent = c.description || '—';
            document.getElementById('case-meta').textContent = `${(c.case_type||'umum').replace('_',' ')} · Dibuat ${new Date(c.created_at).toLocaleDateString('id-ID')}`;

            const normalizedStatus = String(c.status || '').toUpperCase();
            const statusMap = {PENDING:{bg:'#f5f28e',color:'#070607',label:'Menunggu'},IN_PROGRESS:{bg:'#524ae9',color:'#fff',label:'Berlangsung'},ESCALATED:{bg:'#fc5000',color:'#fff',label:'Eskalasi'},COMPLETED:{bg:'#070607',color:'#fff',label:'Selesai'},CANCELLED:{bg:'#e2e2df',color:'rgba(7,6,7,0.5)',label:'Dibatalkan'}};
            const s = statusMap[normalizedStatus] || {bg:'#e2e2df',color:'#070607',label:c.status};
            const badge = document.getElementById('case-status-badge');
            badge.textContent = s.label; badge.style.background = s.bg; badge.style.color = s.color;

            renderActions(c);

            if (normalizedStatus === 'ASSIGNED' || normalizedStatus === 'PENDING') {
                document.getElementById('case-status-badge').textContent = 'Tugas Baru';
                document.getElementById('case-status-badge').style.background = '#f5f28e';
                document.getElementById('case-status-badge').style.color = '#070607';
            }

            loadMessages();
            // Poll for new messages every 3 seconds
            if (!window.chatPollInterval) {
                window.chatPollInterval = setInterval(loadMessages, 3000);
            }
        }).catch(() => {
            document.getElementById('case-detail-loading').textContent = 'Kasus tidak ditemukan.';
        });
}

function renderActions(c) {
    const el = document.getElementById('case-actions');
    const acts = [];
    const normalizedStatus = String(c.status || '').toUpperCase();
    if (['IN_PROGRESS','REVIEWING'].includes(normalizedStatus)) {
        acts.push(`<button onclick="expertComplete()" class="btn-primary" style="width:100%;padding:12px;margin-bottom:8px;">✓ Selesaikan Kasus</button>`);
        if (currentUserRole === 'paralegal') {
            acts.push(`<button onclick="escalateCase()" class="btn-secondary" style="width:100%;padding:12px;color:var(--color-ember);border-color:var(--color-ember);">⬆ Eskalasi ke Pengacara</button>`);
        }
    } else if (normalizedStatus === 'COMPLETED') {
        acts.push(`<p style="text-align:center;font-size:13px;color:rgba(7,6,7,0.4);">Kasus sudah selesai.</p>`);
    } else {
        acts.push(`<p style="text-align:center;font-size:13px;color:rgba(7,6,7,0.4);">Mulai proses kasus di Kanban untuk melihat opsi.</p>`);
    }
    el.innerHTML = acts.join('');
}

function loadMessages() {
    fetch('/api/v1/expert/cases/'+caseId+'/messages', { headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }})
        .then(r => r.json()).then(d => {
            let msgs = d.data || [];
            if (msgs.data && Array.isArray(msgs.data)) msgs = msgs.data;
            const el = document.getElementById('case-messages');
            const isAtBottom = el.scrollHeight - el.scrollTop <= el.clientHeight + 50;
            if (!msgs.length) { el.innerHTML = '<div style="text-align:center;padding:24px;color:rgba(7,6,7,0.4);font-size:13px;">Belum ada pesan.</div>'; return; }
            el.innerHTML = msgs.map(m => {
                const role = m.sender ? m.sender.role : 'client';
                const isMe = Number(m.sender_id) === Number(currentUserId);
                return `<div class="chat-message ${isMe?'chat-message-user':'chat-message-ai'}">
                    <p style="font-size:14px;line-height:1.5;">${escapeHtml(m.message).replace(/\n/g, '<br>')}</p>
                    <p style="font-size:11px;margin-top:4px;opacity:0.5;">${isMe ? 'Anda' : 'Klien'} · ${new Date(m.created_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})}</p>
                </div>`;
            }).join('');
            if (isAtBottom || window.justSentMsg) {
                el.scrollTop = el.scrollHeight;
                window.justSentMsg = false;
            }
        });
}

async function sendMsg() {
    const input = document.getElementById('msg-input');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    const response = await fetch('/api/v1/expert/cases/'+caseId+'/messages', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        input.value = text;
        showToast(data.message || 'Pesan gagal dikirim.', 'error');
        return;
    }
    window.justSentMsg = true;
    loadMessages();
}

async function expertComplete() { 
    if(!confirm('Tandai kasus ini sebagai Selesai?')) return;
    await fetch(`/api/v1/expert/cases/${caseId}/complete`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }
    });
    showToast('Kasus ditandai selesai.');
    loadCase();
}

async function escalateCase() { 
    if(!confirm('Eskalasi kasus ini ke Pengacara?')) return;
    await fetch(`/api/v1/paralegal/cases/${caseId}/escalate`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json' }
    });
    showToast('Kasus berhasil dieskalasi.');
    loadCase();
}

window.sendMsg = sendMsg;
window.expertComplete = expertComplete;
window.escalateCase = escalateCase;
})();
</script>
@endpush
