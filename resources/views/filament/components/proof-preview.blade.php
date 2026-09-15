<div style="text-align:center;">
    <p style="margin-bottom:12px; font-size:13px; color:#6b7280;">
        {{ $record->bank_name }} — {{ $record->sender_name }} — Rp {{ number_format((float)$record->amount, 0, ',', '.') }}
    </p>
    @if($url)
        <img src="{{ $url }}" alt="Bukti transfer" style="max-width:100%; max-height:70vh; border-radius:8px; border:1px solid #e5e7eb;" />
        <p style="margin-top:8px;">
            <a href="{{ $url }}" target="_blank" style="color:#4f46e5; font-size:13px; text-decoration:underline;">Buka di tab baru</a>
        </p>
    @else
        <p style="color:#ef4444;">Bukti tidak ditemukan ({{ $record->proof_path }})</p>
    @endif
</div>
