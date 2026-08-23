<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Login Google — RCI</title>
    <link rel="stylesheet" href="/css/caldera.css">
</head>
<body>
<div class="auth-container">
    <div style="text-align:center;">
        <!-- Logo -->
        <a href="/" style="display:inline-flex; align-items:center; gap:12px; text-decoration:none; margin-bottom:40px;">
            <div class="nav-logo-icon" style="width:56px;height:56px;font-size:28px;">R</div>
            <span class="font-display" style="font-size:36px; color:var(--color-obsidian); letter-spacing:0.02em;">RCI</span>
        </a>

        <div class="card" style="max-width:400px; margin:0 auto; text-align:center;">
            <!-- Spinner state -->
            <div id="state-loading">
                <div style="width:56px;height:56px;border:3px solid var(--color-pumice);border-top-color:var(--color-ember);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 20px;"></div>
                <h2 class="font-display" style="font-size:28px;margin-bottom:8px;letter-spacing:0.02em;">MEMPROSES LOGIN</h2>
                <p style="color:rgba(7,6,7,0.5);font-size:14px;">Menghubungkan akun Google Anda...</p>
            </div>

            <!-- Success state -->
            <div id="state-success" style="display:none;">
                <div style="width:56px;height:56px;background:var(--color-ember);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <svg width="28" height="28" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h2 class="font-display" style="font-size:28px;margin-bottom:8px;letter-spacing:0.02em;">LOGIN BERHASIL!</h2>
                <p style="color:rgba(7,6,7,0.5);font-size:14px;">Mengalihkan ke dashboard Anda...</p>
            </div>

            <!-- Error state -->
            <div id="state-error" style="display:none;">
                <div style="width:56px;height:56px;background:rgba(252,80,0,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <svg width="28" height="28" fill="none" stroke="var(--color-ember)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <h2 class="font-display" style="font-size:28px;margin-bottom:8px;letter-spacing:0.02em;color:var(--color-ember);">LOGIN GAGAL</h2>
                <p id="error-message" style="color:rgba(7,6,7,0.6);font-size:14px;margin-bottom:24px;">Terjadi kesalahan saat login dengan Google.</p>
                <a href="/login" class="btn-primary" style="display:inline-flex;">Coba Lagi</a>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
(function() {
    // Ambil token atau error dari URL hash (#token=xxx atau #error=xxx)
    const hash = window.location.hash.substring(1); // hilangkan '#'
    const params = new URLSearchParams(hash);

    const token = params.get('token');
    const error = params.get('error');

    if (error) {
        document.getElementById('state-loading').style.display = 'none';
        document.getElementById('state-error').style.display = 'block';
        const msgs = {
            'google_auth_failed': 'Autentikasi Google gagal. Pastikan Anda mengizinkan akses yang diminta.',
            'account_suspended': 'Akun Anda sedang dinonaktifkan karena pemeriksaan kepatuhan. Silakan hubungi admin RCI.',
        };
        document.getElementById('error-message').textContent = msgs[error] || 'Terjadi kesalahan. Silakan coba lagi.';
        return;
    }

    if (!token) {
        // Tidak ada token dan tidak ada error — mungkin akses langsung ke halaman ini
        window.location.href = '/login';
        return;
    }

    // Simpan token
    localStorage.setItem('rci_token', token);

    // Fetch user info untuk tau role-nya, lalu redirect ke dashboard yang tepat
    fetch('/api/v1/auth/me', {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        const user = data.user || data;
        const role = user.role;

        document.getElementById('state-loading').style.display = 'none';
        document.getElementById('state-success').style.display = 'block';

        // Redirect sesuai role
        const redirectMap = {
            'paralegal': '/paralegal',
            'lawyer':    '/lawyer',
            'client':    '/client',
        };
        const dest = redirectMap[role] || '/client';

        setTimeout(() => {
            window.location.href = dest;
        }, 1200);
    })
    .catch(() => {
        // Token valid tapi gagal fetch user — tetap redirect ke client
        document.getElementById('state-loading').style.display = 'none';
        document.getElementById('state-success').style.display = 'block';
        setTimeout(() => { window.location.href = '/client'; }, 1200);
    });
})();
</script>
</body>
</html>
