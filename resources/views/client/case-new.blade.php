@extends('layouts.dashboard')
@section('title', 'Buat Kasus Baru — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/client/cases" class="sidebar-nav-item active">
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
</nav>
@endsection

@section('content')
<div style="margin-bottom:32px;">
    <a href="/client/cases" style="color:rgba(7,6,7,0.45);text-decoration:none;font-size:14px;">← Kembali ke Kasus Saya</a>
    <h1 class="font-display text-heading-lg" style="margin-top:8px;">BUAT KASUS <span style="color:var(--color-ember);">BARU</span></h1>
</div>

<div style="max-width:640px;">
    <div class="card">
        <div id="case-error" style="display:none;background:rgba(252,80,0,0.1);border:1.5px solid var(--color-ember);border-radius:var(--radius-medium);padding:12px 16px;margin-bottom:20px;font-size:14px;color:var(--color-ember);"></div>

        <form id="new-case-form" style="display:flex; flex-direction:column; gap:20px;">
            <div>
                <label style="font-size:13px;font-weight:500;display:block;margin-bottom:8px;">Judul Kasus <span style="color:var(--color-ember);">*</span></label>
                <input type="text" id="case-title" class="input-field" placeholder="Contoh: Sengketa PHK dengan perusahaan" required>
            </div>

            <div>
                <label style="font-size:13px;font-weight:500;display:block;margin-bottom:8px;">Jenis Kasus <span style="color:var(--color-ember);">*</span></label>
                <div class="grid-3-cols">
                    @foreach(['perdata'=>'⚖️ Perdata','pidana'=>'🔒 Pidana','tata_usaha'=>'📋 Tata Usaha'] as $val => $label)
                    <label style="display:flex;align-items:center;justify-content:center;gap:6px;padding:14px;border-radius:var(--radius-medium);border:1.5px solid var(--color-pumice);cursor:pointer;font-size:14px;font-weight:500;transition:all 0.15s;text-align:center;" class="case-type-opt" onclick="selectType('{{ $val }}', this)">
                        <input type="radio" name="case_type" value="{{ $val }}" style="display:none;">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
                <input type="hidden" id="case-type-val">
            </div>

            <div>
                <label style="font-size:13px;font-weight:500;display:block;margin-bottom:8px;">Deskripsi Kasus <span style="color:var(--color-ember);">*</span></label>
                <textarea id="case-desc" class="input-field" placeholder="Ceritakan situasi hukum Anda secara detail. Semakin lengkap, semakin tepat analisis yang kami berikan." rows="6" style="border-radius:24px; resize:vertical;" required></textarea>
            </div>

            <div style="background:rgba(82,74,233,0.08);border-radius:var(--radius-medium);padding:16px;font-size:13px;color:rgba(7,6,7,0.6);line-height:1.6;">
                <strong style="color:var(--color-plasma-violet);">💡 Tips:</strong> Sertakan: tanggal kejadian, pihak yang terlibat, kronologi singkat, dan dokumen yang Anda miliki. Identitas dan detail pribadi Anda terlindungi kerahasiaan klien-advokat.
            </div>

            <button type="submit" class="btn-primary" style="width:100%;padding:18px;font-size:16px;" id="submit-case-btn">
                Ajukan Kasus
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
const token = localStorage.getItem('rci_token');

function selectType(val, el) {
    document.querySelectorAll('.case-type-opt').forEach(e => { e.style.borderColor='var(--color-pumice)'; e.style.background=''; });
    el.style.borderColor = 'var(--color-ember)';
    el.style.background = 'rgba(252,80,0,0.06)';
    document.getElementById('case-type-val').value = val;
}

document.getElementById('new-case-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const errBox = document.getElementById('case-error');
    const btn = document.getElementById('submit-case-btn');
    errBox.style.display = 'none';

    const type = document.getElementById('case-type-val').value;
    if (!type) { errBox.textContent = 'Pilih jenis kasus terlebih dahulu.'; errBox.style.display = 'block'; return; }

    btn.textContent = 'Mengajukan...'; btn.disabled = true;

    try {
        const res = await fetch('/api/v1/cases', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer '+token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: document.getElementById('case-title').value,
                case_type: type,
                description: document.getElementById('case-desc').value,
            })
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message||'Gagal');
            throw new Error(msgs);
        }
        showToast('Kasus berhasil diajukan!');
        setTimeout(() => window.location.href = '/client/cases', 1200);
    } catch(err) {
        errBox.textContent = err.message; errBox.style.display = 'block';
        btn.textContent = 'Ajukan Kasus'; btn.disabled = false;
    }
});
window.selectType = selectType;
})();
</script>
@endpush
