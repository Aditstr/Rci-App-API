<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    Clock,
    Inbox,
    Loader2,
    RefreshCw,
    X,
} from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();

const accessState = ref('checking');
const verification = ref({ status: 'missing', rejection_reason: null });

const stats = ref({});
const statsLoading = ref(true);
const statsLoaded = ref(false);
const statsError = ref(null);

const columns = ref([
    {
        key: 'need_action',
        label: 'Menunggu',
        statuses: 'need_action',
        items: [],
        meta: null,
        loading: true,
        loadingMore: false,
        error: null,
    },
    {
        key: 'reviewing',
        label: 'Dikerjakan',
        statuses: 'reviewing',
        items: [],
        meta: null,
        loading: true,
        loadingMore: false,
        error: null,
    },
    {
        key: 'on_hold',
        label: 'Ditunda',
        statuses: 'on_hold',
        items: [],
        meta: null,
        loading: true,
        loadingMore: false,
        error: null,
    },
    {
        key: 'awaiting_confirmation',
        label: 'Menunggu Konfirmasi',
        statuses: 'awaiting_confirmation',
        items: [],
        meta: null,
        loading: true,
        loadingMore: false,
        error: null,
    },
]);

const actionKey = ref(null);
const feedback = ref(null);

const completeOpen = ref(false);
const completeTarget = ref(null);
const completeNotes = ref('');
const completeError = ref(null);
const completeSubmitting = ref(false);

const escalateOpen = ref(false);
const escalateTarget = ref(null);
const escalateLawyerId = ref('');
const escalateError = ref(null);
const escalateSubmitting = ref(false);
const lawyers = ref([]);
const lawyersLoading = ref(false);

const CATEGORY_LABELS = {
    criminal: 'Pidana',
    general: 'Perdata',
    family: 'Keluarga',
    corporate: 'Perusahaan',
    property: 'Properti',
    labor: 'Ketenagakerjaan',
    immigration: 'Imigrasi',
    intellectual_property: 'Kekayaan Intelektual',
    tax: 'Pajak',
};

const EMPTY_MESSAGES = {
    need_action: 'Tidak ada kasus baru. Kasus yang ditugaskan akan muncul di sini.',
    reviewing: 'Belum ada kasus yang sedang dikerjakan.',
    on_hold: 'Tidak ada kasus yang ditunda.',
    awaiting_confirmation: 'Tidak ada kasus yang menunggu konfirmasi klien.',
};

const statCards = computed(() => [
    {
        key: 'new',
        label: 'Kasus Baru',
        value: stats.value.new_cases_count ?? 0,
        hint: 'Menunggu untuk dikerjakan',
        icon: Clock,
        tone: 'bg-[#fc5000]/10 text-[#fc5000]',
    },
    {
        key: 'reviewing',
        label: 'Sedang Dikerjakan',
        value: stats.value.reviewing_count ?? 0,
        hint: 'Sedang berjalan',
        icon: Activity,
        tone: 'bg-black/5 text-[#070607]',
    },
    {
        key: 'completed',
        label: 'Selesai',
        value: stats.value.completed_count ?? 0,
        hint: 'Sudah dikonfirmasi klien',
        icon: CheckCircle2,
        tone: 'bg-emerald-100 text-emerald-700',
    },
]);

const categoryLabel = (category) => CATEGORY_LABELS[category] || category || 'Umum';

const emptyMessage = (key) => EMPTY_MESSAGES[key] || 'Tidak ada kasus.';

const formatDate = (value) => {
    if (!value) return '';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const dashboardFor = (role) =>
    role === 'paralegal' ? '/paralegal' : role === 'lawyer' ? '/lawyer' : '/client';

const apiMessage = (err, fallback) => {
    const data = err?.response?.data;
    const validation = data?.errors
        ? Object.values(data.errors).flat().find(Boolean)
        : null;
    return validation || data?.message || fallback;
};

const isActionBusy = (item, action) => actionKey.value === `${item.id}:${action}`;

const isCardBusy = (item) => actionKey.value?.startsWith(`${item.id}:`) === true;

const loadStats = async () => {
    if (!statsLoaded.value) statsLoading.value = true;
    statsError.value = null;
    try {
        const response = await api.get('/paralegal/dashboard/stats');
        stats.value = response.data?.data ?? {};
        statsLoaded.value = true;
    } catch (err) {
        statsError.value = apiMessage(err, 'Gagal memuat statistik.');
    } finally {
        statsLoading.value = false;
    }
};

const loadColumn = async (column) => {
    column.loading = column.items.length === 0;
    column.error = null;
    try {
        const response = await api.get('/paralegal/cases', {
            params: { status: column.statuses },
        });
        const paginator = response.data?.data ?? {};
        column.items = Array.isArray(paginator.data) ? paginator.data : [];
        column.meta = {
            current_page: paginator.meta?.current_page ?? 1,
            last_page: paginator.meta?.last_page ?? 1,
            total: paginator.meta?.total ?? column.items.length,
        };
    } catch (err) {
        column.error = apiMessage(err, 'Gagal memuat kolom ini.');
    } finally {
        column.loading = false;
    }
};

const loadWorkspace = async () => {
    await Promise.all([loadStats(), ...columns.value.map((column) => loadColumn(column))]);
};

const loadMore = async (column) => {
    if (!column.meta || column.meta.current_page >= column.meta.last_page) return;
    column.loadingMore = true;
    column.error = null;
    try {
        const response = await api.get('/paralegal/cases', {
            params: {
                status: column.statuses,
                page: column.meta.current_page + 1,
            },
        });
        const paginator = response.data?.data ?? {};
        const nextItems = Array.isArray(paginator.data) ? paginator.data : [];
        column.items = [...column.items, ...nextItems];
        column.meta = {
            current_page: paginator.meta?.current_page ?? column.meta.current_page + 1,
            last_page: paginator.meta?.last_page ?? column.meta.last_page,
            total: paginator.meta?.total ?? column.meta.total,
        };
    } catch (err) {
        column.error = apiMessage(err, 'Gagal memuat kasus berikutnya.');
    } finally {
        column.loadingMore = false;
    }
};

const updateStatus = async (item, status) => {
    actionKey.value = `${item.id}:status`;
    feedback.value = null;
    try {
        await api.post(`/paralegal/cases/${item.id}/status`, { status });
        feedback.value = { type: 'success', message: 'Status kasus diperbarui.' };
        await loadWorkspace();
    } catch (err) {
        feedback.value = {
            type: 'error',
            message: apiMessage(err, 'Gagal memperbarui status kasus.'),
        };
    } finally {
        actionKey.value = null;
    }
};

const openComplete = (item) => {
    completeTarget.value = item;
    completeNotes.value = '';
    completeError.value = null;
    completeOpen.value = true;
};

const submitComplete = async () => {
    if (!completeTarget.value) return;
    const notes = completeNotes.value.trim();
    if (!notes) {
        completeError.value = 'Catatan penyelesaian wajib diisi.';
        return;
    }

    completeSubmitting.value = true;
    completeError.value = null;
    try {
        await api.post(`/expert/cases/${completeTarget.value.id}/complete`, {
            completion_notes: notes,
        });
        completeOpen.value = false;
        feedback.value = {
            type: 'success',
            message: 'Kasus ditandai selesai. Menunggu konfirmasi klien.',
        };
        await loadWorkspace();
    } catch (err) {
        completeError.value = apiMessage(err, 'Gagal menyelesaikan kasus.');
    } finally {
        completeSubmitting.value = false;
    }
};

const openEscalate = async (item) => {
    escalateTarget.value = item;
    escalateLawyerId.value = '';
    escalateError.value = null;
    escalateOpen.value = true;
    lawyers.value = [];
    lawyersLoading.value = true;
    try {
        const response = await api.get('/paralegal/lawyers');
        lawyers.value = Array.isArray(response.data?.data) ? response.data.data : [];
    } catch (err) {
        escalateError.value = apiMessage(err, 'Gagal memuat daftar pengacara.');
    } finally {
        lawyersLoading.value = false;
    }
};

const submitEscalate = async () => {
    if (!escalateTarget.value) return;
    if (!escalateLawyerId.value) {
        escalateError.value = 'Pilih pengacara tujuan.';
        return;
    }

    escalateSubmitting.value = true;
    escalateError.value = null;
    try {
        await api.post(`/paralegal/cases/${escalateTarget.value.id}/escalate`, {
            lawyer_id: Number(escalateLawyerId.value),
        });
        escalateOpen.value = false;
        feedback.value = {
            type: 'success',
            message: 'Kasus berhasil dieskalasi ke pengacara.',
        };
        await loadWorkspace();
    } catch (err) {
        escalateError.value = apiMessage(err, 'Gagal mengeskalasi kasus.');
    } finally {
        escalateSubmitting.value = false;
    }
};

const lawyerLabel = (lawyer) => {
    const parts = [lawyer.name];
    if (Array.isArray(lawyer.specializations) && lawyer.specializations.length) {
        parts.push(lawyer.specializations.join(', '));
    }
    if (lawyer.experience_years) {
        parts.push(`${lawyer.experience_years} tahun`);
    }
    return parts.join(' - ');
};

onMounted(async () => {
    await auth.fetchUser();

    if (!auth.isAuthenticated) {
        router.replace({ name: 'login', query: { redirect: '/paralegal' } });
        return;
    }

    if (auth.role !== 'paralegal') {
        accessState.value = 'wrong-role';
        return;
    }

    const profile = auth.user?.expert_profile;
    if (profile?.verification_status === 'approved' || profile?.is_verified === true) {
        accessState.value = 'allowed';
        await loadWorkspace();
        return;
    }

    verification.value = {
        status: profile?.verification_status || 'missing',
        rejection_reason: profile?.rejection_reason || null,
    };
    accessState.value = 'unverified';
});
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] py-8 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto">
            <div v-if="accessState === 'checking'" class="flex min-h-[50vh] items-center justify-center gap-3">
                <Loader2 class="w-5 h-5 animate-spin text-[#fc5000]" />
                <span class="text-sm text-black/60">Memeriksa akses workspace...</span>
            </div>

            <Card v-else-if="accessState === 'wrong-role'" class="max-w-xl mx-auto p-6 sm:p-8 bg-white border-black/10">
                <h1 class="font-display text-2xl font-bold text-[#070607]">
                    Halaman ini untuk akun paralegal
                </h1>
                <p class="mt-2 text-sm text-black/60">
                    Akun Anda terdaftar sebagai {{ auth.role || 'pengguna' }}.
                </p>
                <a :href="dashboardFor(auth.role)" class="mt-5 inline-block">
                    <Button>Buka dashboard saya</Button>
                </a>
            </Card>

            <Card v-else-if="accessState === 'unverified'" class="max-w-xl mx-auto p-6 sm:p-8 bg-white border-black/10">
                <h1 class="font-display text-2xl font-bold text-[#070607]">
                    Verifikasi paralegal diperlukan
                </h1>
                <p v-if="verification.status === 'pending'" class="mt-2 text-sm text-black/60">
                    Dokumen Anda sedang ditinjau admin. Workspace terbuka setelah akun disetujui.
                </p>
                <p v-else-if="verification.status === 'rejected'" class="mt-2 text-sm text-black/60">
                    Pendaftaran ditolak. Alasan:
                    <span class="font-medium text-[#070607]">
                        {{ verification.rejection_reason || 'Tidak disebutkan.' }}
                    </span>
                </p>
                <p v-else class="mt-2 text-sm text-black/60">
                    Dokumen KTP dan ijazah belum lengkap. Unggah dokumen untuk memulai verifikasi.
                </p>
                <a href="/paralegal/verification" class="mt-5 inline-block">
                    <Button>Buka Halaman Verifikasi</Button>
                </a>
            </Card>

            <div v-else class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#f7f6f2] p-5 sm:p-8 rounded-2xl sm:rounded-3xl border border-black/10 shadow-sm">
                    <div>
                        <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight text-[#070607]">
                            Workspace Paralegal
                        </h1>
                        <p class="mt-1 text-sm text-black/60">
                            Kelola kasus yang ditugaskan, perbarui status, dan eskalasi ke advokat.
                        </p>
                    </div>
                    <div>
                        <Button as-child variant="outline" class="min-h-[44px] border-black/20">
                            <a href="/paralegal/marketplace">Lihat Marketplace</a>
                        </Button>
                    </div>
                </div>

                <div
                    v-if="feedback"
                    :role="feedback.type === 'error' ? 'alert' : 'status'"
                    :class="[
                        'flex items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm',
                        feedback.type === 'error'
                            ? 'border-red-200 bg-red-50 text-red-700'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    ]"
                >
                    <p>{{ feedback.message }}</p>
                    <button
                        type="button"
                        class="flex size-11 shrink-0 items-center justify-center rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fc5000]"
                        aria-label="Tutup pesan"
                        @click="feedback = null"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card
                        v-for="card in statCards"
                        :key="card.key"
                        class="p-6 bg-white border border-black/10 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wider font-semibold text-black/70">
                                    {{ card.label }}
                                </p>
                                <div v-if="statsLoading" class="mt-3 h-10 w-16 rounded bg-black/10 animate-pulse" />
                                <p v-else class="mt-2 font-display text-4xl font-bold text-[#070607]">
                                    {{ card.value }}
                                </p>
                                <p class="mt-1 text-xs text-black/60">{{ card.hint }}</p>
                            </div>
                            <div :class="['rounded-lg p-2', card.tone]">
                                <component :is="card.icon" class="w-5 h-5" />
                            </div>
                        </div>
                    </Card>
                </div>

                <div
                    v-if="statsError"
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    role="alert"
                >
                    <div class="flex items-center gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <p>{{ statsError }}</p>
                    </div>
                    <Button variant="outline" size="sm" class="min-h-[44px]" @click="loadStats">
                        <RefreshCw class="w-4 h-4" />
                        Coba lagi
                    </Button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <section
                        v-for="column in columns"
                        :key="column.key"
                        class="flex flex-col rounded-2xl border border-black/10 bg-[#f7f6f2] p-4"
                    >
                        <header class="mb-4 flex items-center justify-between gap-2">
                            <h2 class="font-display text-lg font-bold text-[#070607]">
                                {{ column.label }}
                            </h2>
                            <Badge variant="outline" class="bg-white">
                                {{ column.meta?.total ?? column.items.length }}
                            </Badge>
                        </header>

                        <div v-if="column.loading" class="space-y-3" aria-busy="true">
                            <div
                                v-for="i in 2"
                                :key="i"
                                class="rounded-xl border border-black/10 bg-white p-4"
                            >
                                <div class="h-3 w-20 rounded bg-black/10 animate-pulse" />
                                <div class="mt-3 h-4 w-full rounded bg-black/10 animate-pulse" />
                                <div class="mt-2 h-4 w-2/3 rounded bg-black/10 animate-pulse" />
                                <div class="mt-4 h-9 w-24 rounded bg-black/10 animate-pulse" />
                            </div>
                        </div>

                        <div
                            v-else-if="column.error"
                            class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                            role="alert"
                        >
                            <p>{{ column.error }}</p>
                            <Button
                                variant="outline"
                                size="sm"
                                class="mt-3 min-h-[44px]"
                                @click="loadColumn(column)"
                            >
                                Coba lagi
                            </Button>
                        </div>

                        <div
                            v-else-if="column.items.length === 0"
                            class="rounded-xl border border-dashed border-black/15 p-6 text-center"
                        >
                            <Inbox class="mx-auto w-5 h-5 text-black/30" />
                            <p class="mt-2 text-xs text-black/60">{{ emptyMessage(column.key) }}</p>
                        </div>

                        <div v-else class="space-y-3">
                            <article
                                v-for="item in column.items"
                                :key="item.id"
                                class="rounded-xl border border-black/10 bg-white p-4 shadow-sm"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[11px] font-medium text-black/70">
                                        {{ item.case_number }}
                                    </p>
                                    <Badge variant="outline" class="shrink-0 bg-white text-[10px]">
                                        {{ categoryLabel(item.category) }}
                                    </Badge>
                                </div>

                                <h3 class="mt-2 text-sm font-semibold leading-snug text-[#070607]">
                                    {{ item.title || 'Kasus tanpa judul' }}
                                </h3>
                                <p v-if="item.client?.name" class="mt-1 text-xs text-black/60">
                                    Klien: {{ item.client.name }}
                                </p>
                                <p class="mt-1 text-xs text-black/70">
                                    {{ formatDate(item.assigned_at || item.created_at) }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template v-if="column.key === 'need_action'">
                                        <Button
                                            size="sm"
                                            class="min-h-[44px]"
                                            :disabled="isCardBusy(item)"
                                            @click="updateStatus(item, 'in_progress')"
                                        >
                                            {{ isActionBusy(item, 'status') ? 'Menyimpan...' : 'Mulai' }}
                                        </Button>
                                    </template>

                                    <template v-else-if="column.key === 'reviewing'">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="min-h-[44px]"
                                            :disabled="isCardBusy(item)"
                                            @click="updateStatus(item, 'on_hold')"
                                        >
                                            {{ isActionBusy(item, 'status') ? 'Menyimpan...' : 'Tunda' }}
                                        </Button>
                                        <Button
                                            size="sm"
                                            class="min-h-[44px]"
                                            :disabled="isCardBusy(item)"
                                            @click="openComplete(item)"
                                        >
                                            Selesai
                                        </Button>
                                    </template>

                                    <template v-else-if="column.key === 'on_hold'">
                                        <Button
                                            size="sm"
                                            class="min-h-[44px]"
                                            :disabled="isCardBusy(item)"
                                            @click="updateStatus(item, 'in_progress')"
                                        >
                                            {{ isActionBusy(item, 'status') ? 'Menyimpan...' : 'Lanjutkan' }}
                                        </Button>
                                    </template>

                                    <p
                                        v-else-if="column.key === 'awaiting_confirmation'"
                                        class="text-xs text-black/60"
                                    >
                                        Menunggu konfirmasi klien.
                                    </p>

                                    <Button
                                        v-if="column.key !== 'awaiting_confirmation'"
                                        variant="outline"
                                        size="sm"
                                        class="min-h-[44px]"
                                        :disabled="isCardBusy(item)"
                                        @click="openEscalate(item)"
                                    >
                                        Eskalasi
                                    </Button>
                                </div>

                                <a
                                    :href="`/paralegal/cases/${item.id}`"
                                    class="mt-3 inline-flex min-h-[44px] items-center text-xs font-semibold text-[#070607] underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fc5000] focus-visible:ring-offset-2"
                                >
                                    Buka ruang kerja
                                </a>
                            </article>

                            <Button
                                v-if="column.meta && column.meta.current_page < column.meta.last_page"
                                variant="ghost"
                                size="sm"
                                class="w-full min-h-[44px]"
                                :disabled="column.loadingMore"
                                @click="loadMore(column)"
                            >
                                <Loader2 v-if="column.loadingMore" class="w-4 h-4 animate-spin" />
                                {{ column.loadingMore ? 'Memuat...' : 'Muat lagi' }}
                            </Button>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <Dialog v-model:open="completeOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tandai kasus selesai</DialogTitle>
                    <DialogDescription>
                        Tulis rangkuman hasil kerja. Klien akan mengonfirmasi sebelum kasus
                        dianggap selesai.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <label for="completion-notes" class="text-sm font-medium text-[#070607]">
                        Catatan penyelesaian
                    </label>
                    <Textarea
                        id="completion-notes"
                        v-model="completeNotes"
                        rows="5"
                        placeholder="Contoh: Dokumen telah disusun dan diserahkan kepada klien."
                    />
                    <p v-if="completeError" class="text-sm text-red-600" role="alert">
                        {{ completeError }}
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button
                        variant="outline"
                        class="min-h-[44px]"
                        :disabled="completeSubmitting"
                        @click="completeOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        class="min-h-[44px]"
                        :disabled="completeSubmitting"
                        @click="submitComplete"
                    >
                        {{ completeSubmitting ? 'Menyimpan...' : 'Tandai Selesai' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="escalateOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Eskalasi ke pengacara</DialogTitle>
                    <DialogDescription>
                        Kasus akan dialihkan ke pengacara yang dipilih dan tidak lagi muncul di
                        workspace Anda.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <label for="escalation-lawyer" class="text-sm font-medium text-[#070607]">
                        Pengacara tujuan
                    </label>
                    <select
                        id="escalation-lawyer"
                        v-model="escalateLawyerId"
                        class="min-h-[44px] w-full rounded-md border border-black/20 bg-white px-3 py-2 text-sm text-[#070607] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fc5000]"
                        :disabled="lawyersLoading || lawyers.length === 0"
                    >
                        <option value="">
                            {{ lawyersLoading ? 'Memuat daftar pengacara...' : 'Pilih pengacara' }}
                        </option>
                        <option v-for="lawyer in lawyers" :key="lawyer.id" :value="lawyer.id">
                            {{ lawyerLabel(lawyer) }}
                        </option>
                    </select>
                    <p
                        v-if="!lawyersLoading && lawyers.length === 0 && !escalateError"
                        class="text-sm text-black/60"
                    >
                        Belum ada pengacara aktif yang sudah diverifikasi.
                    </p>
                    <p v-if="escalateError" class="text-sm text-red-600" role="alert">
                        {{ escalateError }}
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button
                        variant="outline"
                        class="min-h-[44px]"
                        :disabled="escalateSubmitting"
                        @click="escalateOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        class="min-h-[44px]"
                        :disabled="escalateSubmitting || !escalateLawyerId"
                        @click="submitEscalate"
                    >
                        {{ escalateSubmitting ? 'Mengeskalasi...' : 'Eskalasi Kasus' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
