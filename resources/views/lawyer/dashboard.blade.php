@extends('layouts.dashboard')
@section('title', 'Dashboard Pengacara — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/lawyer" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/lawyer/cases" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Eskalasi
    </a>
    <a href="/lawyer/revenue" class="sidebar-nav-item">
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

<!-- ONBOARDING CONTAINER -->
<div id="onboarding-container" style="display:none; margin-bottom:32px;">
    <div class="card" style="padding:32px;">
        <h1 class="font-display text-heading-lg" style="margin-bottom:24px;">SOP & Verifikasi <span style="color:var(--color-ember);">Pengacara</span></h1>
        
        <div id="sop-content" style="padding:24px; background:rgba(7,6,7,0.02); border-radius:16px; margin-bottom:32px; border:1px solid rgba(7,6,7,0.05); font-size:15px; line-height:1.6;">
            Memuat SOP...
        </div>

        <h3 class="font-display text-heading" style="margin-bottom:16px;">Unggah Dokumen Verifikasi</h3>
        <p style="color:rgba(7,6,7,0.6); margin-bottom:24px;">Silakan unggah dokumen persyaratan Anda untuk menyetujui SOP dan mendaftar sebagai Pengacara. Dokumen akan diverifikasi secara ketat oleh Admin.</p>

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
                <div>
                    <label class="font-display" style="display:block; margin-bottom:8px;">Kartu Peradi/Lisensi (JPG/PNG/PDF max 5MB)</label>
                    <input type="file" id="license_file" accept=".jpg,.jpeg,.png,.pdf" required style="width:100%; padding:12px; border:1px solid var(--color-pumice); border-radius:8px;">
                </div>
                <div>
                    <label class="font-display" style="display:block; margin-bottom:8px;">Selfie dengan KTP (JPG/PNG max 5MB)</label>
                    <input type="file" id="selfie_file" accept=".jpg,.jpeg,.png" required style="width:100%; padding:12px; border:1px solid var(--color-pumice); border-radius:8px;">
                </div>
                <div class="col-span-full">
                    <label class="font-display" style="display:block; margin-bottom:8px;">Curriculum Vitae / CV (Opsional, PDF/DOC max 10MB)</label>
                    <input type="file" id="cv_file" accept=".pdf,.doc,.docx" style="width:100%; padding:12px; border:1px solid var(--color-pumice); border-radius:8px;">
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
        <p style="color:rgba(7,6,7,0.6); line-height:1.6;">Dokumen pendaftaran Anda telah kami terima dan saat ini sedang ditinjau secara saksama oleh tim Admin. Anda akan mendapatkan akses ke Dashboard setelah akun disetujui.</p>
    </div>
</div>

<!-- DASHBOARD CONTAINER -->
<div id="dashboard-container" style="display:none;">
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
        <span class="tag" style="background:var(--color-obsidian); color:var(--color-chalk); margin-bottom:8px;">⚖️ Pengacara</span>
        <h1 class="font-display text-heading-lg">DASHBOARD<br><span style="color:var(--color-ember);">PENGACARA</span></h1>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:32px;">
    <div class="stat-card">
        <p class="stat-card-label">Kasus Aktif</p>
        <p class="stat-card-value" id="l-active">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Kasus Selesai</p>
        <p class="stat-card-value" id="l-done">—</p>
    </div>
    <div class="stat-card">
        <p class="stat-card-label">Total Revenue</p>
        <p class="stat-card-value" style="font-size:clamp(22px,3vw,56px);" id="l-revenue">—</p>
    </div>
    <div class="card" style="background:var(--color-plasma-violet); position:relative; overflow:hidden;">
        <div class="halftone-overlay" style="opacity:0.15;"></div>
        <p class="stat-card-label" style="color:rgba(255,255,255,0.7);">Rating Klien</p>
        <p class="stat-card-value" id="l-rating">—</p>
    </div>
</div>

<!-- Escalated Cases -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <h2 class="font-display text-heading">KASUS MASUK</h2>
        <span class="tag" id="cases-count" style="background:var(--color-ember); color:white;">0</span>
    </div>
    <div id="lawyer-cases">
        <div style="text-align:center; padding:40px; color:rgba(7,6,7,0.4);">Memuat...</div>
    </div>
</div>

<!-- Revenue Detail -->
<div class="grid-2-cols" style="gap:16px;">
    <div class="card">
        <h3 class="font-display" style="font-size:22px; margin-bottom:20px; letter-spacing:0.02em;">PENAWARAN AKTIF</h3>
        <div id="quotation-list">
            <div style="text-align:center; padding:24px; color:rgba(7,6,7,0.4); font-size:14px;">Belum ada penawaran aktif.</div>
        </div>
    </div>
    <div class="card" style="background:var(--color-obsidian);">
        <h3 class="font-display" style="font-size:22px; margin-bottom:8px; letter-spacing:0.02em; color:var(--color-chalk);">REVENUE BULAN INI</h3>
        <p class="font-display" style="font-size:clamp(32px,4vw,72px); color:var(--color-ember); margin-bottom:16px; letter-spacing:0.02em;" id="l-monthly">—</p>
        <p style="font-size:13px; color:rgba(255,255,255,0.45);">Total royalti profesional yang diterima bulan berjalan.</p>
        <a href="/lawyer/revenue" class="btn-primary" style="margin-top:24px; display:inline-flex;">Riwayat Lengkap →</a>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');

window.onUserLoaded = function(user) {
    const expert = user.expert_profile;
    
    // Alur Onboarding / Pending
    if (!expert || !expert.ktp_path || !expert.ijazah_path || !expert.license_card_path || !expert.selfie_path) {
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
        document.getElementById('dashboard-container').style.display = 'block';
        
        // Stats
        fetch('/api/v1/lawyer/dashboard/stats', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            const s = d.data || d;
            document.getElementById('l-active').textContent  = s.active_cases ?? '—';
            document.getElementById('l-done').textContent    = s.completed_cases ?? '—';
            document.getElementById('l-revenue').textContent = s.total_revenue ? 'Rp '+Number(s.total_revenue).toLocaleString('id-ID') : '—';
            document.getElementById('l-rating').textContent  = s.average_rating ? Number(s.average_rating).toFixed(1)+' ★' : '—';
            document.getElementById('l-monthly').textContent = s.monthly_revenue ? 'Rp '+Number(s.monthly_revenue).toLocaleString('id-ID') : 'Rp 0';
        }).catch(() => {});

        // Cases
        fetch('/api/v1/expert/cases', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            renderCases(d.data || []);
        }).catch(() => {
            document.getElementById('lawyer-cases').innerHTML = '<div style="text-align:center; padding:40px; color:rgba(7,6,7,0.4);">Gagal memuat kasus.</div>';
        });
    }
};

function renderCases(cases) {
    document.getElementById('cases-count').textContent = cases.length;
    const el = document.getElementById('lawyer-cases');
    if (!cases.length) {
        el.innerHTML = '<div style="text-align:center;padding:32px;color:rgba(7,6,7,0.4);font-size:14px;">Belum ada kasus yang dieskalasi.</div>';
        return;
    }
    el.innerHTML = cases.map(c => `
        <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1.5px dotted var(--color-pumice);">
            <div style="flex:1;">
                <p style="font-weight:500;font-size:15px;margin-bottom:4px;">${c.title || 'Kasus #'+c.id}</p>
                <p style="font-size:12px;color:rgba(7,6,7,0.45);">Tipe: ${c.case_type || '—'} · ${new Date(c.created_at).toLocaleDateString('id-ID')}</p>
            </div>
            <span class="tag" style="background:${c.status==='ESCALATED'?'#524ae9':'#fc5000'};color:white;">${c.status}</span>
            <button onclick="openQuote(${c.id})" class="btn-primary" style="padding:8px 16px;font-size:13px;">Beri Penawaran</button>
        </div>
    `).join('');
}

function fetchSOP() {
    fetch('/api/v1/settings/lawyer_sop', {
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
    fd.append('license_card', document.getElementById('license_file').files[0]);
    fd.append('selfie', document.getElementById('selfie_file').files[0]);
    
    const cvFile = document.getElementById('cv_file').files[0];
    if (cvFile) fd.append('cv', cvFile);

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

function openQuote(caseId) {
    const fee = prompt('Masukkan biaya penawaran (Rp):');
    if (!fee || isNaN(fee)) return;
    fetch(`/api/v1/lawyer/cases/${caseId}/quote`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: parseInt(fee), notes: 'Penawaran harga layanan hukum.' })
    }).then(r => {
        if (!r.ok) throw new Error('Gagal mengirim penawaran');
        showToast('Penawaran berhasil dikirim!');
    }).catch(err => showToast(err.message, 'error'));
}
window.openQuote = openQuote;
})();
</script>
@endpush
