@extends('layouts.dashboard')
@section('title', 'Notifikasi — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard</a>
    <a href="/client/cases" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Kasus Saya</a>
    <a href="/client/ai-chat" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Konsultasi AI</a>
    <a href="/client/wallet" class="sidebar-nav-item"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Dompet</a>
    <a href="/client/notifications" class="sidebar-nav-item active"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Notifikasi</a>
</nav>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
    <h1 class="font-display text-heading-lg">NOTIFIKASI</h1>
    <button onclick="markAllRead()" class="btn-ghost" style="font-size:14px;color:var(--color-ember);">Tandai semua dibaca</button>
</div>

<div class="card">
    <div id="notif-list">
        <div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Memuat notifikasi...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const token = localStorage.getItem('rci_token');
window.onUserLoaded = function() { loadNotifs(); };

function loadNotifs() {
    fetch('/api/v1/notifications', { headers: {'Authorization':'Bearer '+token,'Accept':'application/json'}})
        .then(r=>r.json()).then(d=>{
            const notifs = d.data || d || [];
            const el = document.getElementById('notif-list');
            if(!notifs.length){ el.innerHTML='<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);font-size:14px;">Tidak ada notifikasi.</div>'; return; }
            el.innerHTML = notifs.map(n=>{
                const isUnread = !n.read_at;
                return `<div style="display:flex;gap:16px;padding:18px 0;border-bottom:1.5px dotted var(--color-pumice);cursor:pointer;${isUnread?'':'opacity:0.6;'}" onclick="markRead('${n.id}', this)">
                    <div style="width:10px;height:10px;border-radius:50%;background:${isUnread?'var(--color-ember)':'var(--color-pumice)'};flex-shrink:0;margin-top:6px;"></div>
                    <div style="flex:1;">
                        <p style="font-weight:${isUnread?'600':'500'};font-size:15px;margin-bottom:4px;">${n.data?.title || n.type || 'Notifikasi'}</p>
                        <p style="font-size:13px;color:rgba(7,6,7,0.55);line-height:1.5;">${n.data?.message || n.data?.body || ''}</p>
                        <p style="font-size:12px;color:rgba(7,6,7,0.35);margin-top:6px;">${new Date(n.created_at).toLocaleDateString('id-ID',{day:'numeric',month:'long',hour:'2-digit',minute:'2-digit'})}</p>
                    </div>
                </div>`;
            }).join('');
        }).catch(()=>{ document.getElementById('notif-list').innerHTML='<div style="text-align:center;padding:60px;color:rgba(7,6,7,0.4);">Gagal memuat notifikasi.</div>'; });
}

function markRead(id, row) {
    fetch(`/api/v1/notifications/${id}/read`, { method:'POST', headers:{'Authorization':'Bearer '+token,'Accept':'application/json'}})
        .then(()=>{ row.style.opacity='0.6'; row.querySelector('div').style.background='var(--color-pumice)'; });
}
function markAllRead() {
    fetch('/api/v1/notifications/read-all', {method:'POST', headers:{'Authorization':'Bearer '+token,'Accept':'application/json'}})
        .then(()=>{ showToast('Semua notifikasi telah dibaca.'); loadNotifs(); });
}
</script>
@endpush
