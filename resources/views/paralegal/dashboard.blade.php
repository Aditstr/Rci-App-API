@extends('layouts.dashboard')
@section('title', 'Workspace Paralegal — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/paralegal" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/paralegal/kanban" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Kanban Board
    </a>
    <a href="/paralegal/marketplace" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Job Marketplace
    </a>
    <a href="/client/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
</nav>
@endsection

@section('content')

<!-- ONBOARDING CONTAINER -->
<div id="onboarding-container" style="display:none; margin-bottom:32px;">
    <div class="card" style="padding:32px;">
        <h1 class="font-display text-heading-lg" style="margin-bottom:24px;">SOP & Verifikasi <span style="color:var(--color-ember);">Paralegal</span></h1>
        
        <div id="sop-content" style="padding:24px; background:rgba(7,6,7,0.02); border-radius:16px; margin-bottom:32px; border:1px solid rgba(7,6,7,0.05); font-size:15px; line-height:1.6;">
            Memuat SOP...
        </div>

        <h3 class="font-display text-heading" style="margin-bottom:16px;">Unggah Dokumen Wajib</h3>
        <p style="color:rgba(7,6,7,0.6); margin-bottom:24px;">Silakan unggah KTP dan Ijazah Anda untuk menyetujui SOP dan mendaftar sebagai Paralegal. Dokumen akan ditinjau oleh Admin.</p>

        <form id="onboarding-form" onsubmit="submitOnboarding(event)">
            <div class="grid-2-cols" style="margin-bottom:24px;">
                <div>
                    <label class="font-display" style="display:block; margin-bottom:8px;">KTP Asli (JPG/PNG/PDF max 5MB)</label>
                    <input type="file" id="ktp_file" accept=".jpg,.jpeg,.png,.pdf" required style="width:100%; padding:12px; border:1px solid var(--color-pumice); border-radius:8px;">
                </div>
                <div>
                    <label class="font-display" style="display:block; margin-bottom:8px;">Ijazah Terakhir (JPG/PNG/PDF max 5MB)</label>
                    <input type="file" id="ijazah_file" accept=".jpg,.jpeg,.png,.pdf" required style="width:100%; padding:12px; border:1px solid var(--color-pumice); border-radius:8px;">
                </div>
            </div>
            
            <div id="onboarding-error" style="color:var(--color-ember); margin-bottom:16px; display:none;"></div>
            
            <button type="submit" class="btn-primary" id="btn-submit-onboarding" style="width:100%; justify-content:center; padding:16px; font-size:16px;">Saya Setuju dengan SOP & Kirim Dokumen</button>
        </form>
    </div>
</div>

<!-- PENDING CONTAINER -->
<div id="pending-container" style="display:none; text-align:center; padding:64px 24px;">
    <div class="card" style="display:inline-block; padding:48px; max-width:500px;">
        <svg width="48" height="48" fill="none" stroke="var(--color-plasma-violet)" stroke-width="2" style="margin-bottom:24px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <h2 class="font-display text-heading" style="margin-bottom:16px;">Sedang Diverifikasi</h2>
        <p style="color:rgba(7,6,7,0.6); line-height:1.6;">Dokumen pendaftaran Anda telah kami terima dan saat ini sedang ditinjau oleh tim Admin. Anda akan mendapatkan akses ke Dashboard setelah akun disetujui.</p>
    </div>
</div>

<!-- DASHBOARD CONTAINER -->
<div id="dashboard-container" style="display:none;">
<!-- Header -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <span class="tag" style="margin-bottom:8px;">Paralegal</span>
        <h1 class="font-display text-heading-lg">WORKSPACE<br><span style="color:var(--color-ember);">PARALEGAL</span></h1>
    </div>
    <div style="display:flex; gap:12px;">
        <a href="/paralegal/marketplace" class="btn-secondary">Lihat Marketplace</a>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:32px;">
    <div class="stat-card">
        <p class="stat-card-label">Kasus Aktif</p>
        <p class="stat-card-value" id="p-active">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Selesai Bulan Ini</p>
        <p class="stat-card-value" id="p-done">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Total Pendapatan</p>
        <p class="stat-card-value" style="font-size:clamp(24px,3vw,56px);" id="p-revenue">—</p>
    </div>
    <div class="card" style="background:var(--color-plasma-violet); position:relative; overflow:hidden;">
        <div class="halftone-overlay" style="opacity:0.15;"></div>
        <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Rating</p>
        <p class="stat-card-value" id="p-rating">—</p>
    </div>
</div>

<!-- Kanban Board -->
<div class="card" style="padding:32px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <h2 class="font-display text-heading">PAPAN KANBAN</h2>
        <span style="font-size:13px; color:rgba(7,6,7,0.45);">Drag &amp; drop untuk update status</span>
    </div>

    <div class="kanban-board" id="kanban-board">
        <!-- Columns rendered by JS -->
        <div class="kanban-column" id="col-pending">
            <div class="kanban-column-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f5f28e;"></span>
                    <span class="font-display" style="font-size:18px; letter-spacing:0.02em;">MENUNGGU</span>
                </div>
                <span class="tag" id="count-pending">0</span>
            </div>
            <div class="kanban-cards" id="cards-pending" ondragover="event.preventDefault()" ondrop="dropCard(event,'PENDING')"></div>
        </div>

        <div class="kanban-column" id="col-inprogress">
            <div class="kanban-column-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#524ae9;"></span>
                    <span class="font-display" style="font-size:18px; letter-spacing:0.02em;">BERLANGSUNG</span>
                </div>
                <span class="tag" style="background:#524ae9;color:white;" id="count-inprogress">0</span>
            </div>
            <div class="kanban-cards" id="cards-inprogress" ondragover="event.preventDefault()" ondrop="dropCard(event,'IN_PROGRESS')"></div>
        </div>

        <div class="kanban-column" id="col-onhold">
            <div class="kanban-column-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#fc5000;"></span>
                    <span class="font-display" style="font-size:18px; letter-spacing:0.02em;">DITUNDA</span>
                </div>
                <span class="tag" style="background:var(--color-ember);color:white;" id="count-onhold">0</span>
            </div>
            <div class="kanban-cards" id="cards-onhold" ondragover="event.preventDefault()" ondrop="dropCard(event,'ON_HOLD')"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');
let allCases = [];
let draggingId = null;

window.onUserLoaded = function(user) {
    const expert = user.expert_profile;
    
    // Alur Onboarding / Pending
    if (!expert || !expert.ktp_path || !expert.ijazah_path) {
        document.getElementById('onboarding-container').style.display = 'block';
        fetchSOP();
    } else if (expert.verification_status === 'pending' || expert.verification_status === 'rejected') {
        document.getElementById('pending-container').style.display = 'block';
        if (expert.verification_status === 'rejected') {
            document.querySelector('#pending-container h2').textContent = 'Pendaftaran Ditolak';
            document.querySelector('#pending-container p').innerHTML = `Maaf, dokumen Anda ditolak dengan alasan: <strong>${expert.rejection_reason}</strong>.<br><br>Silakan menghubungi admin atau daftar ulang.`;
            document.querySelector('#pending-container svg').style.stroke = 'var(--color-ember)';
        }
    } else {
        // Tampilkan dashboard
        document.getElementById('dashboard-container').style.display = 'block';
        
        // Stats
        fetch('/api/v1/paralegal/dashboard/stats', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            const s = d.data || d;
            document.getElementById('p-active').textContent = s.active_cases ?? '—';
            document.getElementById('p-done').textContent   = s.completed_this_month ?? '—';
            document.getElementById('p-revenue').textContent = s.total_earnings ? 'Rp '+Number(s.total_earnings).toLocaleString('id-ID') : '—';
            document.getElementById('p-rating').textContent = s.average_rating ? Number(s.average_rating).toFixed(1) + ' ★' : '—';
        }).catch(() => {});

        loadKanban();
    }
};

function fetchSOP() {
    fetch('/api/v1/settings/paralegal_sop', {
        headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) {
            document.getElementById('sop-content').innerHTML = d.data;
        } else {
            document.getElementById('sop-content').innerHTML = '<p>Gagal memuat SOP. Silakan hubungi admin.</p>';
        }
    }).catch(() => {
        document.getElementById('sop-content').innerHTML = '<p>Terjadi kesalahan saat memuat SOP.</p>';
    });
}

window.submitOnboarding = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-onboarding');
    const errBox = document.getElementById('onboarding-error');
    errBox.style.display = 'none';
    
    btn.textContent = 'Mengunggah...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('ktp', document.getElementById('ktp_file').files[0]);
    fd.append('ijazah', document.getElementById('ijazah_file').files[0]);

    try {
        const res = await fetch('/api/v1/auth/resubmit-documents', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
            body: fd
        });
        const data = await res.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            throw new Error(data.message || 'Gagal mengunggah dokumen.');
        }
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.textContent = 'Saya Setuju dengan SOP & Kirim Dokumen';
        btn.disabled = false;
    }
}


function loadKanban() {
    fetch('/api/v1/paralegal/cases', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        allCases = d.data || d || [];
        renderKanban();
    }).catch(() => {
        document.getElementById('kanban-board').innerHTML = '<p style="color:rgba(7,6,7,0.4);text-align:center;padding:40px;">Gagal memuat kasus.</p>';
    });
}

function renderKanban() {
    const cols = { PENDING: [], IN_PROGRESS: [], ON_HOLD: [] };
    allCases.forEach(c => {
        if (cols[c.status]) cols[c.status].push(c);
        else if (c.status === 'ESCALATED') cols.IN_PROGRESS.push(c);
    });

    ['PENDING','IN_PROGRESS','ON_HOLD'].forEach(status => {
        const colId = { PENDING:'pending', IN_PROGRESS:'inprogress', ON_HOLD:'onhold' }[status];
        document.getElementById('count-'+colId).textContent = cols[status].length;
        document.getElementById('cards-'+colId).innerHTML = cols[status].length
            ? cols[status].map(c => kanbanCard(c)).join('')
            : '<div style="padding:20px;text-align:center;color:rgba(7,6,7,0.3);font-size:13px;border:1.5px dashed var(--color-pumice);border-radius:16px;">Kosong</div>';
    });
}

function kanbanCard(c) {
    const typeColors = { perdata:'#f5f28e', pidana:'#fc5000', tata_usaha:'#524ae9' };
    const bg = typeColors[c.case_type] || '#e2e2df';
    const color = c.case_type === 'pidana' || c.case_type === 'tata_usaha' ? '#fff' : '#070607';
    return `<div class="kanban-card" draggable="true" ondragstart="dragStart(event,${c.id})" id="case-${c.id}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px;">
            <span class="tag" style="background:${bg};color:${color};font-size:11px;">${(c.case_type||'umum').replace('_',' ')}</span>
            <span style="font-size:11px;color:rgba(7,6,7,0.4);">#${c.id}</span>
        </div>
        <p style="font-weight:500;font-size:14px;margin-bottom:6px;line-height:1.4;">${c.title || c.description?.substring(0,50) || 'Kasus #'+c.id}</p>
        <p style="font-size:12px;color:rgba(7,6,7,0.45);">${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
            <button onclick="escalateCase(${c.id})" style="padding:4px 10px;border-radius:var(--radius-pills);border:1.5px solid var(--color-obsidian);background:none;cursor:pointer;font-size:11px;font-family:var(--font-dm-sans);font-weight:500;transition:background 0.15s;" onmouseover="this.style.background='var(--color-pumice)'" onmouseout="this.style.background='none'">⬆ Eskalasi</button>
        </div>
    </div>`;
}

function dragStart(e, id) { draggingId = id; e.dataTransfer.effectAllowed = 'move'; }

function dropCard(e, newStatus) {
    e.preventDefault();
    if (!draggingId) return;
    fetch(`/api/v1/paralegal/cases/${draggingId}/status`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
    }).then(r => {
        if (!r.ok) throw new Error('Gagal update status');
        const c = allCases.find(x => x.id == draggingId);
        if (c) c.status = newStatus;
        renderKanban();
        showToast('Status kasus diperbarui!');
    }).catch(err => showToast(err.message, 'error'));
    draggingId = null;
}

function escalateCase(id) {
    if (!confirm('Eskalasi kasus ini ke Pengacara?')) return;
    fetch(`/api/v1/paralegal/cases/${id}/escalate`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        showToast('Kasus berhasil dieskalasi!');
        loadKanban();
    }).catch(() => showToast('Gagal eskalasi', 'error'));
}
window.dropCard    = dropCard;
window.escalateCase = escalateCase;
})();
</script>
@endpush
