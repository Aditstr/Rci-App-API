@extends('layouts.dashboard')
@section('title', 'Profil Saya — RCI')

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
    <a href="/client/wallet" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Dompet
    </a>
    <a href="/client/notifications" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifikasi
    </a>
    <a href="/client/profile" class="sidebar-nav-item active">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profil Saya
    </a>
</nav>
@endsection

@section('content')
<div style="margin-bottom:32px;">
    <h1 class="font-display text-heading-lg">PROFIL <span style="color:var(--color-ember);">SAYA</span></h1>
    <p style="color:rgba(7,6,7,0.5); font-size:14px; margin-top:4px;">Kelola informasi akun dan preferensi Anda.</p>
</div>

<div class="grid-aside-2-cols" style="align-items:flex-start;">

    <!-- Avatar Card -->
    <div class="card" style="text-align:center;">
        <div style="width:96px;height:96px;border-radius:50%;background:var(--color-ember);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:40px;color:white;margin:0 auto 20px;" id="profile-avatar">U</div>
        <p class="font-display" style="font-size:22px; letter-spacing:0.02em;" id="profile-name">—</p>
        <p style="font-size:13px; color:rgba(7,6,7,0.45); margin-top:4px;" id="profile-role">—</p>
        <div style="margin-top:16px; padding-top:16px; border-top:1.5px dotted var(--color-pumice);">
            <p style="font-size:12px; color:rgba(7,6,7,0.4);" id="profile-email">—</p>
            <span class="tag" style="margin-top:8px; display:inline-block;" id="profile-verified">Memuat...</span>
        </div>
    </div>

    <!-- Edit Form -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card">
            <h2 class="font-display text-heading" style="margin-bottom:24px;">INFORMASI AKUN</h2>
            <div style="display:grid; gap:16px;">
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Nama Lengkap</label>
                    <input type="text" id="edit-name" class="input-field" placeholder="Nama lengkap Anda">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Nomor Telepon</label>
                    <input type="tel" id="edit-phone" class="input-field" placeholder="+62...">
                </div>
            </div>
            <button onclick="saveProfile()" class="btn-primary" style="margin-top:20px; padding:12px 32px;">Simpan Perubahan</button>
        </div>

        <div class="card">
            <h2 class="font-display text-heading" style="margin-bottom:24px;">GANTI PASSWORD</h2>
            <div style="display:grid; gap:16px;">
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Password Saat Ini</label>
                    <input type="password" id="current-password" class="input-field" placeholder="Password lama">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Password Baru</label>
                    <input type="password" id="new-password" class="input-field" placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm-password" class="input-field" placeholder="Ulangi password baru">
                </div>
            </div>
            <button onclick="changePassword()" class="btn-secondary" style="margin-top:20px; padding:12px 32px;">Ganti Password</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    window.onUserLoaded = function(user) {
        document.getElementById('profile-name').textContent = user.name || '—';
        document.getElementById('profile-email').textContent = user.email || '—';
        document.getElementById('profile-role').textContent = user.role || '—';
        document.getElementById('profile-avatar').textContent = (user.name || 'U')[0].toUpperCase();
        document.getElementById('edit-name').value = user.name || '';
        document.getElementById('edit-phone').value = user.phone || '';

        const verifiedBadge = document.getElementById('profile-verified');
        if (user.email_verified_at) {
            verifiedBadge.textContent = '✓ Email Terverifikasi';
            verifiedBadge.style.background = 'rgba(34,197,94,0.15)';
            verifiedBadge.style.color = '#16a34a';
        } else {
            verifiedBadge.textContent = '⚠ Email Belum Terverifikasi';
            verifiedBadge.style.background = 'rgba(252,80,0,0.1)';
            verifiedBadge.style.color = 'var(--color-ember)';
        }
    };

    async function saveProfile() {
        const token = localStorage.getItem('rci_token');
        const name = document.getElementById('edit-name').value.trim();
        const phone = document.getElementById('edit-phone').value.trim();
        if (!name) { showToast('Nama tidak boleh kosong', 'error'); return; }
        try {
            const res = await fetch('/api/v1/profile', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan');
            showToast('Profil berhasil diperbarui!');
            document.getElementById('profile-name').textContent = name;
            document.getElementById('profile-avatar').textContent = name[0].toUpperCase();
        } catch (e) { showToast(e.message, 'error'); }
    }

    async function changePassword() {
        const token = localStorage.getItem('rci_token');
        const current = document.getElementById('current-password').value;
        const newPwd  = document.getElementById('new-password').value;
        const confirm = document.getElementById('confirm-password').value;
        if (!current || !newPwd) { showToast('Isi semua field password', 'error'); return; }
        if (newPwd !== confirm) { showToast('Konfirmasi password tidak cocok', 'error'); return; }
        if (newPwd.length < 8) { showToast('Password minimal 8 karakter', 'error'); return; }
        try {
            const res = await fetch('/api/v1/profile/password', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: current, password: newPwd, password_confirmation: confirm })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal ganti password');
            showToast('Password berhasil diperbarui!');
            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';
        } catch (e) { showToast(e.message, 'error'); }
    }

    window.saveProfile = saveProfile;
    window.changePassword = changePassword;
})();
</script>
@endpush
