<div id="escalation-modal" onclick="if(event.target === this) closeEscalationModal()" style="display:none;position:fixed;inset:0;background:rgba(7,6,7,0.55);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <form id="escalation-form" class="card" style="width:100%;max-width:520px;">
        <h2 class="font-display text-heading" style="margin-bottom:8px;">PILIH PENGACARA</h2>
        <p style="font-size:14px;color:rgba(7,6,7,0.55);line-height:1.5;margin-bottom:20px;">Kasus akan dialihkan kepada pengacara yang Anda pilih dan tidak lagi muncul di workspace paralegal.</p>

        <label for="escalation-lawyer" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">Pengacara tujuan</label>
        <select id="escalation-lawyer" class="input-field" required disabled style="width:100%;margin-bottom:8px;">
            <option value="">Memuat daftar pengacara...</option>
        </select>
        <p id="escalation-error" style="display:none;color:var(--color-ember);font-size:13px;line-height:1.5;margin:8px 0 16px;"></p>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" id="escalation-submit" class="btn-primary" style="flex:1;justify-content:center;" disabled>Eskalasi Kasus</button>
            <button type="button" onclick="closeEscalationModal()" class="btn-secondary">Batal</button>
        </div>
    </form>
</div>

<script>
(function() {
    let escalationCaseId = null;
    let escalationSuccessCallback = null;

    function apiErrorMessage(data, fallback) {
        const validationMessage = data?.errors
            ? Object.values(data.errors).flat().find(Boolean)
            : null;
        return validationMessage || data?.message || fallback;
    }

    window.openEscalationModal = async function(caseId, onSuccess) {
        const modal = document.getElementById('escalation-modal');
        const select = document.getElementById('escalation-lawyer');
        const submit = document.getElementById('escalation-submit');
        const error = document.getElementById('escalation-error');

        escalationCaseId = caseId;
        escalationSuccessCallback = typeof onSuccess === 'function' ? onSuccess : null;
        modal.style.display = 'flex';
        select.disabled = true;
        submit.disabled = true;
        error.style.display = 'none';
        select.replaceChildren(new Option('Memuat daftar pengacara...', ''));

        try {
            const response = await fetch('/api/v1/paralegal/lawyers', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('rci_token'),
                    'Accept': 'application/json'
                }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(apiErrorMessage(data, 'Gagal memuat daftar pengacara.'));
            }

            const lawyers = Array.isArray(data.data) ? data.data : [];
            select.replaceChildren(new Option(
                lawyers.length ? 'Pilih pengacara...' : 'Belum ada pengacara terverifikasi',
                ''
            ));

            lawyers.forEach(lawyer => {
                const specializations = Array.isArray(lawyer.specializations) && lawyer.specializations.length
                    ? ' · ' + lawyer.specializations.join(', ')
                    : '';
                const experience = lawyer.experience_years
                    ? ` · ${lawyer.experience_years} tahun pengalaman`
                    : '';
                select.add(new Option(`${lawyer.name}${specializations}${experience}`, lawyer.id));
            });

            select.disabled = lawyers.length === 0;
            submit.disabled = lawyers.length === 0;
            if (!lawyers.length) {
                error.textContent = 'Belum ada pengacara aktif yang sudah diverifikasi oleh admin.';
                error.style.display = 'block';
            }
        } catch (err) {
            select.replaceChildren(new Option('Daftar pengacara tidak tersedia', ''));
            error.textContent = err.message;
            error.style.display = 'block';
        }
    };

    window.closeEscalationModal = function() {
        document.getElementById('escalation-modal').style.display = 'none';
        escalationCaseId = null;
        escalationSuccessCallback = null;
    };

    document.getElementById('escalation-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        const select = document.getElementById('escalation-lawyer');
        const submit = document.getElementById('escalation-submit');
        const error = document.getElementById('escalation-error');
        const lawyerId = Number(select.value);

        if (!escalationCaseId || !lawyerId) {
            error.textContent = 'Silakan pilih pengacara tujuan.';
            error.style.display = 'block';
            return;
        }

        submit.disabled = true;
        submit.textContent = 'Mengeskalasi...';
        error.style.display = 'none';

        try {
            const response = await fetch(`/api/v1/paralegal/cases/${escalationCaseId}/escalate`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('rci_token'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lawyer_id: lawyerId })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(apiErrorMessage(data, 'Kasus gagal dieskalasi.'));
            }

            const callback = escalationSuccessCallback;
            closeEscalationModal();
            showToast(data.message || 'Kasus berhasil dieskalasi ke pengacara.');
            if (callback) callback(data.data);
        } catch (err) {
            error.textContent = err.message;
            error.style.display = 'block';
        } finally {
            submit.disabled = false;
            submit.textContent = 'Eskalasi Kasus';
        }
    });
})();
</script>
