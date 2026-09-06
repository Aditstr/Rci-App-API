<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    ArrowLeft,
    MessageSquare,
    FileText,
    Calendar,
    User,
    Clock,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Loader2,
    Scale,
    Upload,
    ChevronRight,
    DollarSign,
    ShieldCheck,
    Hash,
    Tag,
    CircleDot,
    Ban
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();

const caseData = ref(null);
const loading = ref(true);
const error = ref(null);
const actionLoading = ref(null); // 'approve' | 'reject' | 'cancel' | 'confirm' | 'dispute'
const actionError = ref(null);

const statusConfig = {
    submitted: { label: 'Diajukan', color: 'bg-blue-100 text-blue-700', step: 1 },
    assigned: { label: 'Ditugaskan', color: 'bg-indigo-100 text-indigo-700', step: 2 },
    in_progress: { label: 'Dalam Proses', color: 'bg-amber-100 text-amber-700', step: 3 },
    quoted: { label: 'Menunggu Persetujuan', color: 'bg-orange-100 text-orange-700', step: 3 },
    expert_completed: { label: 'Menunggu Konfirmasi', color: 'bg-teal-100 text-teal-700', step: 4 },
    completed: { label: 'Selesai', color: 'bg-emerald-100 text-emerald-700', step: 5 },
    cancelled: { label: 'Dibatalkan', color: 'bg-red-100 text-red-700', step: -1 },
    disputed: { label: 'Sengketa', color: 'bg-red-100 text-red-700', step: -1 },
};

const timelineSteps = [
    { key: 'submitted', label: 'Diajukan', icon: FileText },
    { key: 'assigned', label: 'Ditugaskan', icon: User },
    { key: 'in_progress', label: 'Dalam Proses', icon: Clock },
    { key: 'expert_completed', label: 'Selesai Expert', icon: CheckCircle2 },
    { key: 'completed', label: 'Selesai', icon: ShieldCheck },
];

const currentStep = computed(() => {
    if (!caseData.value) return 0;
    const conf = statusConfig[caseData.value.status];
    return conf ? conf.step : 0;
});

const statusBadge = computed(() => {
    if (!caseData.value) return { label: '-', color: '' };
    return statusConfig[caseData.value.status] || { label: caseData.value.status, color: 'bg-slate-100 text-slate-700' };
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(val || 0);
};

const fetchCase = async () => {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.get(`/cases/${route.params.id}`);
        caseData.value = res.data.data || res.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal memuat detail kasus.';
    } finally {
        loading.value = false;
    }
};

const handleAction = async (action, payload = {}) => {
    actionLoading.value = action;
    actionError.value = null;
    try {
        const endpoints = {
            approve: `/cases/${route.params.id}/quotation/approve`,
            reject: `/cases/${route.params.id}/quotation/reject`,
            cancel: `/cases/${route.params.id}/cancel`,
            confirm: `/cases/${route.params.id}/confirm-completion`,
            dispute: `/cases/${route.params.id}/dispute`,
        };
        await api.post(endpoints[action], payload);
        await fetchCase(); // Refresh
    } catch (err) {
        actionError.value = err.response?.data?.message || 'Aksi gagal. Silakan coba lagi.';
    } finally {
        actionLoading.value = null;
    }
};

onMounted(fetchCase);
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] py-8 px-4 sm:px-8">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Back -->
            <button
                @click="router.push('/client')"
                class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition-colors"
            >
                <ArrowLeft class="w-4 h-4" />
                Kembali ke Dashboard
            </button>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-20">
                <Loader2 class="w-8 h-8 animate-spin text-[#fc5000]" />
            </div>

            <!-- Error -->
            <Alert v-else-if="error" variant="destructive">
                <AlertTriangle class="w-4 h-4" />
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Case Content -->
            <template v-else-if="caseData">
                <!-- Header Card -->
                <Card class="border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 sm:p-8 text-white">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-slate-400 text-xs">
                                    <Hash class="w-3.5 h-3.5" />
                                    <span>{{ caseData.case_number || `#${caseData.id}` }}</span>
                                </div>
                                <h1 class="text-xl sm:text-2xl font-bold">
                                    {{ caseData.title }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-300">
                                    <span class="flex items-center gap-1.5">
                                        <Tag class="w-3.5 h-3.5" />
                                        {{ caseData.category || 'Umum' }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ formatDate(caseData.submitted_at || caseData.created_at) }}
                                    </span>
                                </div>
                            </div>
                            <Badge :class="statusBadge.color" class="px-3 py-1 text-xs font-semibold rounded-full shrink-0">
                                {{ statusBadge.label }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Timeline Progress -->
                    <div class="p-6 bg-white border-b border-slate-100">
                        <div class="flex items-center justify-between">
                            <div
                                v-for="(step, idx) in timelineSteps"
                                :key="step.key"
                                class="flex items-center flex-1"
                            >
                                <div class="flex flex-col items-center text-center">
                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center mb-1.5 transition-all"
                                        :class="currentStep >= idx + 1
                                            ? 'bg-[#fc5000] text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-400'"
                                    >
                                        <component :is="step.icon" class="w-4 h-4" />
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-medium"
                                        :class="currentStep >= idx + 1 ? 'text-slate-900' : 'text-slate-400'">
                                        {{ step.label }}
                                    </span>
                                </div>
                                <div
                                    v-if="idx < timelineSteps.length - 1"
                                    class="flex-1 h-0.5 mx-2 rounded-full transition-colors"
                                    :class="currentStep > idx + 1 ? 'bg-[#fc5000]' : 'bg-slate-200'"
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Action Error -->
                <Alert v-if="actionError" variant="destructive">
                    <AlertTriangle class="w-4 h-4" />
                    <AlertDescription>{{ actionError }}</AlertDescription>
                </Alert>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Description + Documents -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Description -->
                        <Card class="border border-slate-200 shadow-sm">
                            <CardContent class="p-6 space-y-4">
                                <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-[#fc5000]" />
                                    Deskripsi Kasus
                                </h3>
                                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                                    {{ caseData.description }}
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Quotation Card (if quoted) -->
                        <Card v-if="caseData.status === 'quoted' && caseData.proposed_fee" class="border-2 border-orange-300 shadow-sm bg-orange-50/50">
                            <CardContent class="p-6 space-y-4">
                                <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <DollarSign class="w-4 h-4 text-orange-600" />
                                    Penawaran Biaya Advokat
                                </h3>
                                <div class="bg-white rounded-xl p-4 border border-orange-200">
                                    <div class="text-2xl font-bold text-slate-900">
                                        {{ formatCurrency(caseData.proposed_fee) }}
                                    </div>
                                    <p v-if="caseData.fee_notes" class="text-sm text-slate-600 mt-2">
                                        {{ caseData.fee_notes }}
                                    </p>
                                </div>
                                <div class="flex gap-3">
                                    <Button
                                        @click="handleAction('approve')"
                                        :disabled="actionLoading !== null"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 flex-1"
                                    >
                                        <Loader2 v-if="actionLoading === 'approve'" class="w-4 h-4 animate-spin" />
                                        <CheckCircle2 v-else class="w-4 h-4" />
                                        Setujui
                                    </Button>
                                    <Button
                                        @click="handleAction('reject')"
                                        :disabled="actionLoading !== null"
                                        variant="outline"
                                        class="gap-2 border-red-300 text-red-600 hover:bg-red-50 flex-1"
                                    >
                                        <XCircle class="w-4 h-4" />
                                        Tolak
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Completion Actions -->
                        <Card v-if="caseData.status === 'expert_completed'" class="border-2 border-teal-300 shadow-sm bg-teal-50/50">
                            <CardContent class="p-6 space-y-4">
                                <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-teal-600" />
                                    Expert Telah Menyelesaikan Kasus
                                </h3>
                                <p class="text-sm text-slate-600">
                                    Paralegal/Advokat telah menandai kasus ini sebagai selesai. Silakan konfirmasi atau ajukan sengketa jika ada masalah.
                                </p>
                                <div class="flex gap-3">
                                    <Button
                                        @click="handleAction('confirm')"
                                        :disabled="actionLoading !== null"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 flex-1"
                                    >
                                        <Loader2 v-if="actionLoading === 'confirm'" class="w-4 h-4 animate-spin" />
                                        <CheckCircle2 v-else class="w-4 h-4" />
                                        Konfirmasi Selesai
                                    </Button>
                                    <Button
                                        @click="handleAction('dispute', { reason: 'Klien tidak puas dengan hasil' })"
                                        :disabled="actionLoading !== null"
                                        variant="outline"
                                        class="gap-2 border-red-300 text-red-600 hover:bg-red-50 flex-1"
                                    >
                                        <AlertTriangle class="w-4 h-4" />
                                        Sengketa
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Documents -->
                        <Card v-if="caseData.documents && caseData.documents.length > 0" class="border border-slate-200 shadow-sm">
                            <CardContent class="p-6 space-y-4">
                                <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <Upload class="w-4 h-4 text-[#fc5000]" />
                                    Dokumen ({{ caseData.documents.length }})
                                </h3>
                                <div class="space-y-2">
                                    <a
                                        v-for="doc in caseData.documents"
                                        :key="doc.id"
                                        :href="doc.url || doc.file_url || '#'"
                                        target="_blank"
                                        class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors text-sm"
                                    >
                                        <FileText class="w-4 h-4 text-slate-500 shrink-0" />
                                        <span class="text-slate-700 truncate flex-1">{{ doc.original_name || doc.filename || 'Dokumen' }}</span>
                                        <ChevronRight class="w-4 h-4 text-slate-400" />
                                    </a>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-6">
                        <!-- Chat CTA -->
                        <Card class="border border-slate-200 shadow-sm">
                            <CardContent class="p-6 text-center space-y-4">
                                <div class="w-12 h-12 rounded-full bg-[#fc5000]/10 text-[#fc5000] flex items-center justify-center mx-auto">
                                    <MessageSquare class="w-6 h-6" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 text-sm">Chat Room Kasus</h4>
                                    <p class="text-xs text-slate-500 mt-1">Komunikasi langsung dengan Paralegal atau Advokat yang menangani kasus Anda.</p>
                                </div>
                                <router-link :to="`/client/cases/${route.params.id}/chat`">
                                    <Button class="w-full bg-[#fc5000] hover:bg-[#e04700] text-white rounded-xl gap-2">
                                        <MessageSquare class="w-4 h-4" />
                                        Buka Chat
                                    </Button>
                                </router-link>
                            </CardContent>
                        </Card>

                        <!-- Expert Info -->
                        <Card v-if="caseData.expert" class="border border-slate-200 shadow-sm">
                            <CardContent class="p-6 space-y-4">
                                <h4 class="font-semibold text-slate-900 text-sm flex items-center gap-2">
                                    <Scale class="w-4 h-4 text-indigo-600" />
                                    Ahli Hukum
                                </h4>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                                        {{ (caseData.expert.name || 'E').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ caseData.expert.name }}</p>
                                        <p class="text-xs text-slate-500 capitalize">{{ caseData.expert.role || 'Paralegal' }}</p>
                                    </div>
                                </div>
                                <div v-if="caseData.expert.expert_profile" class="text-xs text-slate-500 space-y-1 pt-2 border-t border-slate-100">
                                    <p v-if="caseData.expert.expert_profile.specialization">
                                        <strong>Spesialisasi:</strong> {{ caseData.expert.expert_profile.specialization }}
                                    </p>
                                    <p v-if="caseData.expert.expert_profile.license_number">
                                        <strong>Lisensi:</strong> {{ caseData.expert.expert_profile.license_number }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Cancel Button -->
                        <Button
                            v-if="['submitted', 'assigned'].includes(caseData.status)"
                            @click="handleAction('cancel', { reason: 'Dibatalkan oleh klien' })"
                            :disabled="actionLoading !== null"
                            variant="outline"
                            class="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50 rounded-xl"
                        >
                            <Ban class="w-4 h-4" />
                            Batalkan Kasus
                        </Button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
