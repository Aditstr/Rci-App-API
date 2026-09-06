<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    ArrowLeft,
    Send,
    User,
    Scale,
    Loader2,
    AlertTriangle,
    Paperclip,
    MoreVertical,
    Phone,
    Info
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const messages = ref([]);
const caseInfo = ref(null);
const inputMessage = ref('');
const sending = ref(false);
const loadingMessages = ref(true);
const loadingCase = ref(true);
const error = ref(null);
const chatContainer = ref(null);
let pollInterval = null;

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const fetchCaseInfo = async () => {
    loadingCase.value = true;
    try {
        const res = await api.get(`/cases/${route.params.id}`);
        caseInfo.value = res.data.data || res.data;
    } catch (err) {
        // Non-critical, still show chat
    } finally {
        loadingCase.value = false;
    }
};

const fetchMessages = async (scroll = true) => {
    try {
        const res = await api.get(`/cases/${route.params.id}/messages`);
        const data = res.data.data || res.data;
        messages.value = Array.isArray(data) ? data : (data.data || []);
        if (scroll) scrollToBottom();
    } catch (err) {
        if (loadingMessages.value) {
            error.value = err.response?.data?.message || 'Gagal memuat pesan.';
        }
    } finally {
        loadingMessages.value = false;
    }
};

const handleSend = async () => {
    const content = inputMessage.value.trim();
    if (!content || sending.value) return;

    inputMessage.value = '';
    sending.value = true;

    // Optimistic UI
    const tempMsg = {
        id: `temp-${Date.now()}`,
        user_id: auth.user?.id,
        message: content,
        content: content,
        sender: auth.user,
        created_at: new Date().toISOString(),
        _sending: true,
    };
    messages.value.push(tempMsg);
    scrollToBottom();

    try {
        await api.post(`/cases/${route.params.id}/messages`, { message: content });
        // Remove temp & refetch
        messages.value = messages.value.filter(m => m.id !== tempMsg.id);
        await fetchMessages();
    } catch (err) {
        // Mark as failed
        const idx = messages.value.findIndex(m => m.id === tempMsg.id);
        if (idx !== -1) {
            messages.value[idx]._failed = true;
            messages.value[idx]._sending = false;
        }
    } finally {
        sending.value = false;
    }
};

const markAsRead = async () => {
    try {
        await api.put(`/cases/${route.params.id}/messages/read`);
    } catch (e) {
        // Silent
    }
};

const isOwnMessage = (msg) => {
    return msg.user_id === auth.user?.id || msg.sender_id === auth.user?.id;
};

const getMessageContent = (msg) => {
    return msg.message || msg.content || '';
};

const getSenderName = (msg) => {
    if (msg.sender?.name) return msg.sender.name;
    if (msg.user?.name) return msg.user.name;
    return isOwnMessage(msg) ? auth.userName : 'Expert';
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};

onMounted(async () => {
    await Promise.all([fetchCaseInfo(), fetchMessages()]);
    markAsRead();
    // Poll every 5 seconds for new messages
    pollInterval = setInterval(() => fetchMessages(false), 5000);
});

onBeforeUnmount(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] flex flex-col">

        <!-- Top Bar -->
        <div class="sticky top-0 z-40 bg-white border-b border-slate-200 px-4 sm:px-6 py-3">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        @click="router.push(`/client/cases/${route.params.id}`)"
                        class="text-slate-500 hover:text-slate-900 transition-colors shrink-0"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-sm text-slate-900 truncate">
                            {{ caseInfo?.title || 'Chat Kasus' }}
                        </h2>
                        <p class="text-xs text-slate-500 truncate">
                            {{ caseInfo?.case_number || `Kasus #${route.params.id}` }}
                            <span v-if="caseInfo?.expert">
                                · {{ caseInfo.expert.name }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="gap-1.5 text-xs hidden sm:flex">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aktif
                    </Badge>
                    <router-link :to="`/client/cases/${route.params.id}`">
                        <Button variant="ghost" size="icon" class="text-slate-400 hover:text-slate-700">
                            <Info class="w-5 h-5" />
                        </Button>
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-hidden">
            <div class="max-w-4xl mx-auto h-full flex flex-col px-4 sm:px-6">

                <!-- Loading -->
                <div v-if="loadingMessages" class="flex-1 flex items-center justify-center">
                    <Loader2 class="w-8 h-8 animate-spin text-[#fc5000]" />
                </div>

                <!-- Error -->
                <div v-else-if="error" class="flex-1 flex items-center justify-center">
                    <div class="text-center space-y-2">
                        <AlertTriangle class="w-8 h-8 text-red-400 mx-auto" />
                        <p class="text-sm text-slate-600">{{ error }}</p>
                        <Button @click="fetchMessages()" variant="outline" size="sm">Coba Lagi</Button>
                    </div>
                </div>

                <!-- Chat Stream -->
                <div v-else ref="chatContainer" class="flex-1 overflow-y-auto py-6 space-y-3">

                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-center">
                        <div class="space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                                <Send class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="font-semibold text-slate-700">Belum ada pesan</p>
                                <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">
                                    Mulai percakapan dengan Paralegal atau Advokat yang menangani kasus Anda.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        :class="[
                            'flex gap-2.5',
                            isOwnMessage(msg) ? 'justify-end' : 'justify-start'
                        ]"
                    >
                        <!-- Expert Avatar -->
                        <Avatar v-if="!isOwnMessage(msg)" class="h-8 w-8 shrink-0 mt-1">
                            <AvatarFallback class="bg-indigo-600 text-white text-xs font-semibold">
                                {{ getSenderName(msg).charAt(0).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>

                        <div :class="['max-w-[75%] sm:max-w-[65%]', isOwnMessage(msg) ? 'text-right' : 'text-left']">
                            <!-- Sender name (for expert messages) -->
                            <p v-if="!isOwnMessage(msg)" class="text-[10px] font-medium text-slate-500 mb-0.5 px-1">
                                {{ getSenderName(msg) }}
                            </p>
                            <div
                                :class="[
                                    'px-4 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-line inline-block text-left',
                                    isOwnMessage(msg)
                                        ? msg._failed
                                            ? 'bg-red-100 text-red-700 rounded-tr-sm'
                                            : 'bg-[#fc5000] text-white rounded-tr-sm'
                                        : 'bg-white border border-slate-200 text-slate-800 rounded-tl-sm shadow-xs'
                                ]"
                            >
                                {{ getMessageContent(msg) }}
                                <span v-if="msg._failed" class="block text-[10px] mt-1 text-red-500 font-medium">
                                    ⚠ Gagal terkirim
                                </span>
                            </div>
                            <p
                                class="text-[10px] mt-0.5 px-1"
                                :class="isOwnMessage(msg) ? 'text-slate-400' : 'text-slate-400'"
                            >
                                {{ formatTime(msg.created_at) }}
                                <span v-if="msg._sending" class="text-slate-400">· Mengirim...</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Bar -->
        <div class="sticky bottom-0 bg-white border-t border-slate-200 px-4 sm:px-6 py-3">
            <div class="max-w-4xl mx-auto">
                <form @submit.prevent="handleSend" class="flex items-center gap-2">
                    <Input
                        v-model="inputMessage"
                        placeholder="Ketik pesan..."
                        class="flex-1 bg-slate-50 border-slate-200"
                        :disabled="sending"
                        @keyup.enter.prevent="handleSend"
                    />
                    <Button
                        type="submit"
                        size="icon"
                        :disabled="sending || !inputMessage.trim()"
                        class="h-10 w-10 rounded-full bg-[#fc5000] hover:bg-[#e04700] text-white shrink-0"
                    >
                        <Loader2 v-if="sending" class="w-4 h-4 animate-spin" />
                        <Send v-else class="w-4 h-4" />
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
