<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — RCI</title>
    <link rel="stylesheet" href="/css/caldera.css">
</head>
<body>
<div class="auth-container">
    <div style="width:100%; max-width:480px;">

        <!-- Logo -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="/" style="display:inline-flex; align-items:center; gap:12px; text-decoration:none;">
                <div class="nav-logo-icon" style="width:48px;height:48px;font-size:24px;">R</div>
                <span class="font-display" style="font-size:32px; color:var(--color-obsidian); letter-spacing:0.02em;">RCI</span>
            </a>
        </div>

        <div class="auth-card">
            <h1 class="font-display text-heading-lg" style="margin-bottom:8px;">MASUK</h1>
            <p style="color:rgba(7,6,7,0.55); font-size:14px; margin-bottom:32px;">Selamat datang kembali di Roys Counsel Indonesia</p>

            <!-- Error alert -->
            <div id="login-error" style="display:none; background:rgba(252,80,0,0.1); border:1.5px solid var(--color-ember); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:20px; font-size:14px; color:var(--color-ember);"></div>

            <form id="login-form" style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Email</label>
                    <input type="email" id="login-email" class="input-field" placeholder="anda@email.com" required>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="login-password" class="input-field" placeholder="Kata sandi Anda" required style="padding-right:48px;">
                        <button type="button" onclick="togglePwd('login-password', this)" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(7,6,7,0.4);">👁</button>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <a href="/forgot-password" style="font-size:13px; color:var(--color-ember); text-decoration:none;">Lupa password?</a>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding:16px; font-size:16px; margin-top:8px;" id="login-btn">
                    Masuk
                </button>
            </form>

            <hr class="dotted-divider-h">

            <!-- Google OAuth -->
            <a href="/api/v1/auth/google" class="btn-secondary" style="width:100%; display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:24px;">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Lanjutkan dengan Google
            </a>

            <p style="text-align:center; font-size:14px; color:rgba(7,6,7,0.55);">
                Belum punya akun? <a href="/register" style="color:var(--color-ember); font-weight:500; text-decoration:none;">Daftar gratis</a>
            </p>
        </div>
    </div>
</div>


<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('login-btn');
    const errBox = document.getElementById('login-error');
    btn.textContent = 'Memproses...';
    btn.disabled = true;
    errBox.style.display = 'none';

    try {
        const res = await fetch('/api/v1/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                email: document.getElementById('login-email').value,
                password: document.getElementById('login-password').value,
            })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Login gagal');
        localStorage.setItem('rci_token', data.token || data.access_token);
        // Redirect by role
        const role = data.user?.role;
        if (role === 'paralegal') window.location.href = '/paralegal';
        else if (role === 'lawyer') window.location.href = '/lawyer';
        else window.location.href = '/client';
    } catch(err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.textContent = 'Masuk';
        btn.disabled = false;
    }
});
</script>
</body>
</html>
