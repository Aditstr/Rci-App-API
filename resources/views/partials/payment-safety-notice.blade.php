@php($isClientNotice = ($audience ?? 'client') === 'client')
<div data-testid="payment-safety-notice" style="padding:14px 18px;border:1.5px solid rgba(252,80,0,0.28);background:rgba(252,80,0,0.07);border-radius:18px;display:flex;gap:12px;align-items:flex-start;">
    <div style="width:32px;height:32px;border-radius:50%;background:var(--color-ember);color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;">!</div>
    <div>
        <p style="font-weight:700;font-size:14px;margin-bottom:3px;">{{ $isClientNotice ? 'Bayar hanya melalui RCI' : 'Pembayaran wajib melalui RCI' }}</p>
        <p style="font-size:12px;line-height:1.55;color:rgba(7,6,7,0.62);">
            @if($isClientNotice)
                Jangan transfer ke rekening pribadi expert. Jika Anda diminta membayar lewat WhatsApp, rekening pribadi, atau di luar escrow RCI, jangan bayar dan laporkan pesan tersebut.
            @else
                Dilarang meminta klien mentransfer ke rekening pribadi atau mengalihkan transaksi ke luar sistem. Pesan berisiko dapat diblokir dan ditinjau admin.
            @endif
        </p>
    </div>
</div>
