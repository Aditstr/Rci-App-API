<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil — RCI</title>
    <link rel="stylesheet" href="/css/caldera.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--color-chalk); }
        .success-card { text-align:center; padding:60px 48px; max-width:480px; width:100%; }
        .icon-wrap { width:100px; height:100px; border-radius:50%; background:rgba(34,197,94,0.1); display:flex; align-items:center; justify-content:center; margin:0 auto 32px; }
        .checkmark { animation: pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        @keyframes pop { 0%{transform:scale(0)} 100%{transform:scale(1)} }
        .countdown { font-size:13px; color:rgba(7,6,7,0.4); margin-top:12px; }
    </style>
</head>
<body>
<div class="success-card">
    <div class="icon-wrap">
        <svg class="checkmark" width="52" height="52" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="26" cy="26" r="24"/>
            <polyline points="16 26 22 32 36 18"/>
        </svg>
    </div>
    <h1 class="font-display" style="font-size:clamp(32px,5vw,56px); letter-spacing:0.02em; margin-bottom:16px;">PEMBAYARAN<br><span style="color:#22c55e;">BERHASIL</span></h1>
    <p style="font-size:16px; color:rgba(7,6,7,0.6); line-height:1.7; margin-bottom:32px;">Transaksi Anda telah dikonfirmasi. Saldo dompet atau layanan Anda akan diperbarui dalam beberapa saat.</p>
    <a href="/client/wallet" class="btn-primary" style="padding:16px 40px; font-size:16px; text-decoration:none; display:inline-block;">Kembali ke Dompet →</a>
    <p class="countdown" id="countdown-text">Mengalihkan otomatis dalam <strong id="sec">5</strong> detik...</p>
</div>
<script>
    let s = 5;
    const t = setInterval(() => {
        s--;
        const el = document.getElementById('sec');
        if (el) el.textContent = s;
        if (s <= 0) { clearInterval(t); window.location.href = '/client/wallet'; }
    }, 1000);
</script>
</body>
</html>
