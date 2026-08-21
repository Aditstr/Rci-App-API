<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — RCI</title>
    <link rel="stylesheet" href="/css/caldera.css">
</head>
<body>
<div class="auth-container" style="padding:48px 32px; align-items:flex-start;">
    <div style="width:100%; max-width:540px; margin:0 auto;">

        <div style="text-align:center; margin-bottom:32px;">
            <a href="/" style="display:inline-flex; align-items:center; gap:12px; text-decoration:none;">
                <div class="nav-logo-icon" style="width:48px;height:48px;font-size:24px;">R</div>
                <span class="font-display" style="font-size:32px; color:var(--color-obsidian); letter-spacing:0.02em;">RCI</span>
            </a>
        </div>

        <div class="auth-card">
            <h1 class="font-display text-heading-lg" style="margin-bottom:8px;">DAFTAR</h1>
            <p style="color:rgba(7,6,7,0.55); font-size:14px; margin-bottom:24px;">Mulai perjalanan hukum Anda bersama RCI hari ini.</p>

            <!-- Google Sign Up -->
            <a href="/api/v1/auth/google" class="btn-secondary" style="width:100%; display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:20px; padding:14px;">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Daftar dengan Google
            </a>

            <!-- Divider -->
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div style="flex:1; height:1.5px; background:var(--color-pumice);"></div>
                <span style="font-size:12px; color:rgba(7,6,7,0.4); white-space:nowrap;">atau daftar dengan email</span>
                <div style="flex:1; height:1.5px; background:var(--color-pumice);"></div>
            </div>

            <div id="reg-error" style="display:none; background:rgba(252,80,0,0.1); border:1.5px solid var(--color-ember); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:16px; font-size:14px; color:var(--color-ember);"></div>
            <div id="reg-success" style="display:none; background:rgba(82,74,233,0.1); border:1.5px solid var(--color-plasma-violet); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:16px; font-size:14px; color:var(--color-plasma-violet);"></div>

            <!-- Role Picker -->
            <div style="margin-bottom:20px;">
                <label style="font-size:13px; font-weight:500; display:block; margin-bottom:10px;">Daftar sebagai</label>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;" id="role-picker">
                    <button type="button" onclick="pickRole('client', this)" class="role-btn active" data-role="client"
                        style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-ember); background:rgba(252,80,0,0.08); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        👤<br>Klien
                    </button>
                    <button type="button" onclick="pickRole('paralegal', this)" class="role-btn" data-role="paralegal"
                        style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        🧑‍💼<br>Paralegal
                    </button>
                    <button type="button" onclick="pickRole('lawyer', this)" class="role-btn" data-role="lawyer"
                        style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        ⚖️<br>Pengacara
                    </button>
                </div>
                <input type="hidden" id="reg-role" value="client">
            </div>

            <!-- Expert notice (no KTP field — upload after login) -->
            <div id="expert-notice" style="display:none; background:rgba(82,74,233,0.07); border:1.5px solid rgba(82,74,233,0.2); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:16px; font-size:13px; color:rgba(7,6,7,0.6); line-height:1.6;">
                💼 <strong style="color:var(--color-plasma-violet);">Akun Expert</strong> — Setelah mendaftar, Anda akan diminta melengkapi dokumen verifikasi (KTP, Ijazah, dll) melalui dashboard.
            </div>

            <form id="reg-form" style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Nama Lengkap</label>
                    <input type="text" id="reg-name" class="input-field" placeholder="Nama Anda" required>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Email</label>
                    <input type="email" id="reg-email" class="input-field" placeholder="anda@email.com" required>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="reg-password" class="input-field" placeholder="Minimal 8 karakter" required style="padding-right:48px;">
                        <button type="button" onclick="togglePwd('reg-password')" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(7,6,7,0.4);">👁</button>
                    </div>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Konfirmasi Password</label>
                    <div style="position:relative;">
                        <input type="password" id="reg-password2" class="input-field" placeholder="Ulangi password" required style="padding-right:48px;">
                        <button type="button" onclick="togglePwd('reg-password2')" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(7,6,7,0.4);">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding:16px; font-size:16px; margin-top:8px;" id="reg-btn">
                    Buat Akun
                </button>
            </form>

            <hr class="dotted-divider-h">
            <p style="text-align:center; font-size:14px; color:rgba(7,6,7,0.55);">
                Sudah punya akun? <a href="/login" style="color:var(--color-ember); font-weight:500; text-decoration:none;">Masuk</a>
            </p>
        </div>
    </div>
</div>

<script>
let selectedRole = 'client';

function togglePwd(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function pickRole(role, btn) {
    selectedRole = role;
    document.getElementById('reg-role').value = role;
    document.querySelectorAll('.role-btn').forEach(b => {
        b.style.border = '1.5px solid var(--color-pumice)';
        b.style.background = 'var(--color-pumice)';
    });
    btn.style.border = '1.5px solid var(--color-ember)';
    btn.style.background = 'rgba(252,80,0,0.08)';

    // Show/hide expert notice (no KTP field)
    const notice = document.getElementById('expert-notice');
    notice.style.display = (role !== 'client') ? 'block' : 'none';
}

document.getElementById('reg-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('reg-btn');
    const errBox = document.getElementById('reg-error');
    const okBox  = document.getElementById('reg-success');
    errBox.style.display = 'none';
    okBox.style.display  = 'none';

    const pwd  = document.getElementById('reg-password').value;
    const pwd2 = document.getElementById('reg-password2').value;
    if (pwd !== pwd2) {
        errBox.textContent = 'Password tidak cocok.';
        errBox.style.display = 'block';
        return;
    }
    if (pwd.length < 8) {
        errBox.textContent = 'Password minimal 8 karakter.';
        errBox.style.display = 'block';
        return;
    }

    btn.textContent = 'Mendaftar...';
    btn.disabled = true;

    const body = {
        name:                  document.getElementById('reg-name').value.trim(),
        email:                 document.getElementById('reg-email').value.trim(),
        password:              pwd,
        password_confirmation: pwd2,
        role:                  selectedRole,
    };

    try {
        const res = await fetch('/api/v1/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'Gagal mendaftar');
            throw new Error(msgs);
        }
        okBox.textContent = '✓ Akun berhasil dibuat! Cek email Anda untuk verifikasi, lalu masuk.';
        okBox.style.display = 'block';
        setTimeout(() => window.location.href = '/login', 2800);
    } catch(err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.textContent = 'Buat Akun';
        btn.disabled = false;
    }
});
</script>
</body>
</html>
