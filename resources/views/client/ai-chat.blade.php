@extends('layouts.dashboard')
@section('title', 'Konsultasi AI — RCI')

@section('sidebar-nav')
<nav style="display:flex; flex-direction:column; gap:4px;">
    <a href="/client" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/client/cases" class="sidebar-nav-item">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Kasus Saya
    </a>
    <a href="/client/ai-chat" class="sidebar-nav-item active">
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
<div style="margin-bottom:24px;">
    <h1 class="font-display text-heading-lg">KONSULTASI <span style="color:var(--color-ember);">AI HUKUM</span></h1>
    <p style="color:rgba(7,6,7,0.55); font-size:14px; margin-top:4px;">Tanyakan masalah hukum Anda. AI kami terlatih dengan hukum Indonesia.</p>
</div>

<!-- Chat Container -->
<div class="chat-container" style="height:calc(100vh - 220px);">
    <!-- Chat header -->
    <div style="display:flex; align-items:center; gap:12px; padding:16px 24px; border-bottom:1.5px dotted var(--color-pumice);">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--color-ember);display:flex;align-items:center;justify-content:center;">
            <svg width="20" height="20" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
            <p style="font-weight:500; font-size:15px;">RCI Legal AI</p>
            <div style="display:flex; align-items:center; gap:6px;">
                <div style="width:7px;height:7px;border-radius:50%;background:#22c55e;"></div>
                <p style="font-size:12px; color:rgba(7,6,7,0.45);">Online</p>
            </div>
        </div>
        <div style="margin-left:auto;">
            <button onclick="clearChat()" class="btn-ghost" style="font-size:13px; color:rgba(7,6,7,0.45);">Bersihkan Riwayat</button>
        </div>
    </div>

    <!-- Messages -->
    <div class="chat-messages" id="chat-messages">
        <!-- Welcome message -->
        <div class="chat-message chat-message-ai" style="max-width:85%;">
            <p style="font-size:15px; margin-bottom:4px;">👋 Halo! Saya adalah AI Legal RCI, asisten hukum Anda.</p>
            <p style="font-size:14px; line-height:1.6; color:rgba(7,6,7,0.7);">Saya siap membantu Anda memahami hak-hak hukum dan memberi analisis awal atas situasi hukum Anda. Apa yang ingin Anda konsultasikan hari ini?</p>
            <p style="font-size:11px; color:rgba(7,6,7,0.35); margin-top:8px;">Hari ini, {{ now()->format('H:i') }}</p>
        </div>

        <!-- Suggested questions -->
        <div style="display:flex; gap:8px; flex-wrap:wrap; padding:4px 0;">
            @foreach(['Hak saya sebagai konsumen?', 'Cara lapor PHK tidak adil?', 'Prosedur gugatan perdata?', 'Hak anak dalam perceraian?'] as $q)
            <button onclick="sendSuggestion(this.textContent)" class="tag" style="cursor:pointer; border:none; font-size:13px; padding:6px 14px; transition:opacity 0.15s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">{{ $q }}</button>
            @endforeach
        </div>
    </div>

    <!-- Input Bar -->
    <div class="chat-input-bar">
        <textarea id="chat-input" placeholder="Ketik pertanyaan hukum Anda..." style="flex:1; padding:12px 20px; border-radius:100px; border:1.5px solid var(--color-pumice); background:var(--color-pumice); font-family:var(--font-dm-sans); font-weight:500; font-size:15px; resize:none; outline:none; min-height:48px; max-height:120px; line-height:1.5; transition:border-color 0.15s;" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}" onfocus="this.style.borderColor='var(--color-ember)'" onblur="this.style.borderColor='var(--color-pumice)'"></textarea>
        <button onclick="sendMessage()" class="btn-primary" id="send-btn" style="padding:12px 24px; flex-shrink:0;">
            Kirim
        </button>
    </div>
</div>

<!-- Usage notice -->
<div style="display:flex; align-items:center; gap:8px; padding:12px 0; color:rgba(7,6,7,0.4); font-size:12px;">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Respons AI bersifat informatif dan bukan merupakan nasihat hukum resmi. Untuk kasus kompleks, gunakan layanan Paralegal atau Pengacara kami.
</div>
@endsection

@push('scripts')
<script>
(function() {
    const msgContainer = document.getElementById('chat-messages');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let isLoading = false;
    const HISTORY_KEY = 'rci_chat_history';

    function getToken() {
        return localStorage.getItem('rci_token');
    }

    function loadHistory() {
        try {
            const saved = localStorage.getItem(HISTORY_KEY);
            if (!saved) return;
            const messages = JSON.parse(saved);
            if (Array.isArray(messages) && messages.length > 0) {
                msgContainer.innerHTML = '';
                messages.forEach(m => {
                    addMessage(m.text, m.role, m.time, false);
                });
            }
        } catch(e) {
            console.error('Failed to load chat history', e);
        }
    }

    function saveMessage(text, role, timeStr) {
        try {
            let history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
            history.push({ text, role, time: timeStr });
            if (history.length > 50) history = history.slice(-50);
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        } catch(e) {
            console.error('Failed to save chat message', e);
        }
    }

    function addMessage(text, role, time, persist = true) {
        const timeStr = time || new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'chat-message ' + (isUser ? 'chat-message-user' : 'chat-message-ai');
        div.style.maxWidth = '80%';
        div.innerHTML = `<p style="font-size:15px; line-height:1.6;">${text.replace(/\n/g,'<br>')}</p>
            <p style="font-size:11px; margin-top:6px; opacity:0.5; text-align:${isUser?'right':'left'}">${timeStr}</p>`;
        msgContainer.appendChild(div);
        msgContainer.scrollTop = msgContainer.scrollHeight;

        if (persist) {
            saveMessage(text, role, timeStr);
        }
        return div;
    }

    function addTyping() {
        const div = document.createElement('div');
        div.className = 'chat-message chat-message-ai';
        div.id = 'typing-indicator';
        div.innerHTML = `<div style="display:flex;gap:5px;align-items:center;padding:4px 0;">
            <span style="width:8px;height:8px;border-radius:50%;background:rgba(7,6,7,0.3);animation:bounce 1s infinite 0s;"></span>
            <span style="width:8px;height:8px;border-radius:50%;background:rgba(7,6,7,0.3);animation:bounce 1s infinite 0.15s;"></span>
            <span style="width:8px;height:8px;border-radius:50%;background:rgba(7,6,7,0.3);animation:bounce 1s infinite 0.3s;"></span>
        </div>
        <style>@keyframes bounce{0%,80%,100%{transform:scale(0.8)}40%{transform:scale(1.2)}}</style>`;
        msgContainer.appendChild(div);
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text || isLoading) return;

        isLoading = true;
        input.value = '';
        input.style.height = 'auto';
        const btn = document.getElementById('send-btn');
        btn.disabled = true;
        btn.textContent = '...';

        addMessage(text, 'user');
        addTyping();

        try {
            const token = getToken();
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            if (token && token !== 'null' && token !== 'undefined') {
                headers['Authorization'] = 'Bearer ' + token;
            }

            let res = await fetch('/api/v1/rci/chat', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({ message: text })
            });

            // Fallback to freemium endpoint (/api/v1/chat/send) on ANY non-OK status (401, 403, 404, 500, etc.)
            if (!res.ok) {
                res = await fetch('/api/v1/chat/send', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ message: text })
                });
            }

            const data = await res.json();
            document.getElementById('typing-indicator')?.remove();

            if (res.ok) {
                const aiAnswer = data.data?.answer 
                    || data.answer 
                    || data.reply 
                    || data.response 
                    || (data.success && data.message ? data.message : null) 
                    || 'Terima kasih atas pertanyaan Anda. Silakan jelaskan rincian kronologi kasus Anda.';
                addMessage(aiAnswer, 'ai');
            } else {
                const errorMsg = data.message || 'Batas pertanyaan harian telah tercapai. Silakan coba lagi nanti atau hubungi Paralegal kami.';
                addMessage('ℹ️ ' + errorMsg, 'ai');
            }
        } catch(err) {
            document.getElementById('typing-indicator')?.remove();
            addMessage('Terima kasih atas pertanyaan Anda. Permasalahan yang Anda sampaikan berkaitan dengan hukum di Indonesia. Silakan berikan rincian kronologi atau konsultasikan langsung dengan tim Paralegal kami.', 'ai');
        }

        isLoading = false;
        btn.disabled = false;
        btn.textContent = 'Kirim';
    }

    function sendSuggestion(text) {
        document.getElementById('chat-input').value = text;
        sendMessage();
    }

    function clearChat() {
        localStorage.removeItem(HISTORY_KEY);
        msgContainer.innerHTML = `
            <div class="chat-message chat-message-ai" style="max-width:85%;">
                <p style="font-size:15px; margin-bottom:4px;">👋 Halo! Saya adalah AI Legal RCI, asisten hukum Anda.</p>
                <p style="font-size:14px; line-height:1.6; color:rgba(7,6,7,0.7);">Saya siap membantu Anda memahami hak-hak hukum dan memberi analisis awal atas situasi hukum Anda. Apa yang ingin Anda konsultasikan hari ini?</p>
                <p style="font-size:11px; color:rgba(7,6,7,0.35); margin-top:8px;">💬 Riwayat percakapan telah dibersihkan.</p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; padding:4px 0;">
                <button onclick="window.sendSuggestion(this.textContent)" class="tag" style="cursor:pointer; border:none; font-size:13px; padding:6px 14px; transition:opacity 0.15s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">Hak saya sebagai konsumen?</button>
                <button onclick="window.sendSuggestion(this.textContent)" class="tag" style="cursor:pointer; border:none; font-size:13px; padding:6px 14px; transition:opacity 0.15s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">Cara lapor PHK tidak adil?</button>
                <button onclick="window.sendSuggestion(this.textContent)" class="tag" style="cursor:pointer; border:none; font-size:13px; padding:6px 14px; transition:opacity 0.15s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">Prosedur gugatan perdata?</button>
                <button onclick="window.sendSuggestion(this.textContent)" class="tag" style="cursor:pointer; border:none; font-size:13px; padding:6px 14px; transition:opacity 0.15s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">Hak anak dalam perceraian?</button>
            </div>`;
    }

    // Attach to window so onclick handlers in HTML template can call them
    window.sendMessage = sendMessage;
    window.sendSuggestion = sendSuggestion;
    window.clearChat = clearChat;

    // Load history on initial page load
    loadHistory();

    // Auto-resize textarea
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }
})();
</script>
@endpush
