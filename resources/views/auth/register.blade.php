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
            <p style="color:rgba(7,6,7,0.55); font-size:14px; margin-bottom:28px;">Mulai perjalanan hukum Anda bersama RCI hari ini.</p>

            <div id="reg-error" style="display:none; background:rgba(252,80,0,0.1); border:1.5px solid var(--color-ember); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:16px; font-size:14px; color:var(--color-ember);"></div>
            <div id="reg-success" style="display:none; background:rgba(82,74,233,0.1); border:1.5px solid var(--color-plasma-violet); border-radius:var(--radius-medium); padding:12px 16px; margin-bottom:16px; font-size:14px; color:var(--color-plasma-violet);"></div>

            <!-- Role Picker -->
            <div style="margin-bottom:20px;">
                <label style="font-size:13px; font-weight:500; display:block; margin-bottom:10px;">Daftar sebagai</label>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;" id="role-picker">
                    <button type="button" onclick="pickRole('client', this)" class="role-btn active" data-role="client" style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-ember); background:rgba(252,80,0,0.08); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        👤<br>Klien
                    </button>
                    <button type="button" onclick="pickRole('paralegal', this)" class="role-btn" data-role="paralegal" style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        🧑‍💼<br>Paralegal
                    </button>
                    <button type="button" onclick="pickRole('lawyer', this)" class="role-btn" data-role="lawyer" style="padding:12px 8px; border-radius:var(--radius-medium); border:1.5px solid var(--color-pumice); background:var(--color-pumice); cursor:pointer; font-family:var(--font-dm-sans); font-weight:500; font-size:13px; text-align:center; transition:all 0.15s;">
                        ⚖️<br>Pengacara
                    </button>
                </div>
                <input type="hidden" id="reg-role" value="client">
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
                    <input type="password" id="reg-password" class="input-field" placeholder="Minimal 8 karakter" required>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">Konfirmasi Password</label>
                    <input type="password" id="reg-password2" class="input-field" placeholder="Ulangi password" required>
                </div>

                <!-- Expert fields -->
                <div id="expert-fields" style="display:none; flex-direction:column; gap:14px;">
                    <hr class="dotted-divider-h" style="margin:4px 0;">
                    <p style="font-size:13px; color:rgba(7,6,7,0.5);">Dokumen verifikasi akan diminta setelah pendaftaran.</p>
                    <div>
                        <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">No. KTP</label>
                        <input type="text" id="reg-ktp" class="input-field" placeholder="16 digit NIK">
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

function pickRole(role, btn) {
    selectedRole = role;
    document.getElementById('reg-role').value = role;
    document.querySelectorAll('.role-btn').forEach(b => {
        b.style.border = '1.5px solid var(--color-pumice)';
        b.style.background = 'var(--color-pumice)';
    });
    btn.style.border = '1.5px solid var(--color-ember)';
    btn.style.background = 'rgba(252,80,0,0.08)';
    const expFields = document.getElementById('expert-fields');
    expFields.style.display = (role !== 'client') ? 'flex' : 'none';
}

document.getElementById('reg-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('reg-btn');
    const errBox = document.getElementById('reg-error');
    const okBox = document.getElementById('reg-success');
    errBox.style.display = 'none';
    okBox.style.display = 'none';

    const pwd = document.getElementById('reg-password').value;
    const pwd2 = document.getElementById('reg-password2').value;
    if (pwd !== pwd2) {
        errBox.textContent = 'Password tidak cocok.';
        errBox.style.display = 'block';
        return;
    }

    btn.textContent = 'Mendaftar...';
    btn.disabled = true;

    const body = {
        name: document.getElementById('reg-name').value,
        email: document.getElementById('reg-email').value,
        password: pwd,
        password_confirmation: pwd2,
        role: selectedRole,
    };
    if (selectedRole !== 'client') {
        body.nik = document.getElementById('reg-ktp').value;
    }

    try {
        const res = await fetch('/api/v1/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal mendaftar');
            throw new Error(msgs);
        }
        okBox.textContent = 'Akun berhasil dibuat! Cek email Anda untuk verifikasi.';
        okBox.style.display = 'block';
        setTimeout(() => window.location.href = '/login', 2500);
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
