<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
    Sparkles, 
    Send, 
    Bot, 
    User, 
    AlertTriangle, 
    Loader2, 
    RefreshCw 
} from 'lucide-vue-next';

const route = useRoute();
const auth = useAuthStore();

const messages = ref([
    {
        role: 'assistant',
        content: 'Halo! Saya adalah **RCI AI Legal Assistant** siap memberikan analisis awal dan referensi hukum perundang-undangan Indonesia. Ada masalah hukum apa yang bisa saya bantu diskusikan hari ini?',
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    },
]);

const inputPrompt = ref('');
const sending = ref(false);
const chatContainer = ref(null);

const suggestions = [
    'Syarat sah perjanjian menurut Pasal 1320 KUHPerdata',
    'Langkah hukum menghadapi somasi wanprestasi',
    'Hak karyawan jika terkena PHK sepihak UU Cipta Kerja',
    'Prosedur pendaftaran hak paten atau merek dagang',
];

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const handleSend = async (customText = null) => {
    const query = (customText || inputPrompt.value).trim();
    if (!query || sending.value) return;

    inputPrompt.value = '';
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    messages.value.push({
        role: 'user',
        content: query,
        time: nowTime,
    });
    scrollToBottom();

    sending.value = true;
    try {
        // Choose endpoint: /chat/send (freemium) or /rci/chat (authenticated)
        const endpoint = auth.isAuthenticated ? '/rci/chat' : '/chat/send';
        const response = await api.post(endpoint, {
            message: query,
            question: query, // support both schemas
        });

        const reply =
            response.data.reply ||
            response.data.answer ||
            response.data.data?.reply ||
            response.data.message ||
            'Maaf, tidak dapat memproses jawaban saat ini.';

        messages.value.push({
            role: 'assistant',
            content: reply,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        });
    } catch (err) {
        messages.value.push({
            role: 'assistant',
            content:
                err.response?.data?.message ||
                'Terjadi kendala jaringan saat menghubungi layanan AI. Silakan coba kembali sesaat lagi.',
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            isError: true,
        });
    } finally {
        sending.value = false;
        scrollToBottom();
    }
};

onMounted(() => {
    if (route.query.q) {
        inputPrompt.value = String(route.query.q);
        handleSend();
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#e2e2df] py-6 px-4 sm:px-8 flex flex-col items-center">
        <div class="w-full max-w-5xl flex-1 flex flex-col">
            <!-- Header bar -->
            <div class="flex items-center justify-between bg-[#f7f6f2] p-4 sm:p-6 rounded-[28px] border border-black/10 shadow-sm mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#fc5000] text-white flex items-center justify-center shadow-md">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="font-display text-xl sm:text-2xl font-bold tracking-tight text-[#070607]">
                            KONSULTASI AI HUKUM
                        </h2>
                        <p class="text-xs text-black/50">
                            Model Analisis Regulasi & Peraturan Perundang-Undangan Indonesia
                        </p>
                    </div>
                </div>

                <Badge variant="secondary" class="gap-1.5 text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Online
                </Badge>
            </div>

            <!-- Chat message container -->
            <Card class="flex-1 bg-[#f7f6f2] p-4 sm:p-6 rounded-[32px] border-black/10 shadow-sm flex flex-col min-h-[500px] max-h-[70vh] overflow-hidden">
                <!-- Message stream -->
                <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-4 pr-2">
                    <div
                        v-for="(msg, index) in messages"
                        :key="index"
                        :class="[
                            'flex gap-3 max-w-[85%] sm:max-w-[75%]',
                            msg.role === 'user' ? 'ml-auto flex-row-reverse' : ''
                        ]"
                    >
                        <Avatar class="h-8 w-8 shrink-0 mt-1">
                            <AvatarFallback :class="msg.role === 'user' ? 'bg-[#070607]' : 'bg-[#fc5000]'">
                                <User v-if="msg.role === 'user'" class="w-4 h-4 text-white" />
                                <Bot v-else class="w-4 h-4 text-white" />
                            </AvatarFallback>
                        </Avatar>

                        <div
                            :class="[
                                'p-4 rounded-3xl text-sm leading-relaxed whitespace-pre-line shadow-xs',
                                msg.role === 'user'
                                    ? 'bg-[#fc5000] text-white rounded-tr-none'
                                    : msg.isError
                                    ? 'bg-red-50 text-red-700 border border-red-200 rounded-tl-none'
                                    : 'bg-white text-[#070607] border border-black/5 rounded-tl-none'
                            ]"
                        >
                            {{ msg.content }}
                            <div
                                :class="[
                                    'text-[10px] mt-2 text-right',
                                    msg.role === 'user' ? 'text-white/70' : 'text-black/40'
                                ]"
                            >
                                {{ msg.time }}
                            </div>
                        </div>
                    </div>

                    <!-- Typing indicator -->
                    <div v-if="sending" class="flex gap-3 max-w-[75%]">
                        <Avatar class="h-8 w-8 shrink-0">
                            <AvatarFallback class="bg-[#fc5000]">
                                <Bot class="w-4 h-4 text-white" />
                            </AvatarFallback>
                        </Avatar>
                        <div class="p-4 rounded-3xl rounded-tl-none bg-white border border-black/5 flex items-center gap-2 text-xs text-black/50">
                            <Loader2 class="w-4 h-4 animate-spin text-[#fc5000]" />
                            <span>Menganalisis regulasi hukum terkait...</span>
                        </div>
                    </div>
                </div>

                <!-- Suggestions chips -->
                <div class="pt-4 border-t border-black/5 mt-4">
                    <p class="text-[11px] font-semibold text-black/40 uppercase mb-2">Contoh Topik Cepat:</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="s in suggestions"
                            :key="s"
                            type="button"
                            @click="handleSend(s)"
                            class="text-xs bg-white hover:bg-[#fc5000]/10 hover:text-[#fc5000] text-black/70 px-3 py-1.5 rounded-full border border-black/10 transition-colors text-left"
                        >
                            {{ s }}
                        </button>
                    </div>
                </div>

                <!-- Input area -->
                <div class="pt-4">
                    <form @submit.prevent="handleSend()" class="flex items-center gap-2">
                        <Input
                            v-model="inputPrompt"
                            placeholder="Tulis pertanyaan hukum Anda di sini..."
                            class="flex-1 bg-white border-black/15 shadow-none"
                            :disabled="sending"
                        />
                        <Button
                            type="submit"
                            size="icon"
                            class="h-12 w-12 rounded-full shrink-0 shadow-sm"
                            :disabled="sending || !inputPrompt.trim()"
                        >
                            <Send class="w-5 h-5" />
                        </Button>
                    </form>

                    <!-- Disclaimer -->
                    <div class="flex items-center gap-1.5 justify-center mt-3 text-[11px] text-black/45 text-center">
                        <AlertTriangle class="w-3.5 h-3.5 text-[#fc5000] shrink-0" />
                        <span>Jawaban AI bersifat informasi awal dan bukan nasihat hukum mengikat secara formal.</span>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
