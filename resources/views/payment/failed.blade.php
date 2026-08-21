<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal — RCI</title>
    <link rel="stylesheet" href="/css/caldera.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--color-chalk); }
        .fail-card { text-align:center; padding:60px 48px; max-width:480px; width:100%; }
        .icon-wrap { width:100px; height:100px; border-radius:50%; background:rgba(252,80,0,0.1); display:flex; align-items:center; justify-content:center; margin:0 auto 32px; animation: shake 0.4s ease; }
        @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-8px)} 40%{transform:translateX(8px)} 60%{transform:translateX(-6px)} 80%{transform:translateX(6px)} }
        .countdown { font-size:13px; color:rgba(7,6,7,0.4); margin-top:12px; }
    </style>
</head>
<body>
<div class="fail-card">
    <div class="icon-wrap">
        <svg width="52" height="52" fill="none" stroke="var(--color-ember)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="26" cy="26" r="24"/>
            <line x1="18" y1="18" x2="34" y2="34"/>
            <line x1="34" y1="18" x2="18" y2="34"/>
        </svg>
    </div>
    <h1 class="font-display" style="font-size:clamp(32px,5vw,56px); letter-spacing:0.02em; margin-bottom:16px;">PEMBAYARAN<br><span style="color:var(--color-ember);">GAGAL</span></h1>
    <p style="font-size:16px; color:rgba(7,6,7,0.6); line-height:1.7; margin-bottom:32px;">Transaksi tidak berhasil diproses atau batas waktu habis. Silakan coba lagi atau gunakan metode pembayaran lain.</p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="/client/wallet" class="btn-primary" style="padding:16px 32px; font-size:15px; text-decoration:none; display:inline-block;">Coba Lagi →</a>
        <a href="/client" class="btn-secondary" style="padding:16px 32px; font-size:15px; text-decoration:none; display:inline-block;">Kembali ke Dashboard</a>
    </div>
    <p class="countdown" id="countdown-text">Mengalihkan otomatis dalam <strong id="sec">8</strong> detik...</p>
</div>
<script>
    let s = 8;
    const t = setInterval(() => {
        s--;
        const el = document.getElementById('sec');
        if (el) el.textContent = s;
        if (s <= 0) { clearInterval(t); window.location.href = '/client/wallet'; }
    }, 1000);
</script>
</body>
</html>
