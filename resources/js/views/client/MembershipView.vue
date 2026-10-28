<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { quotaLabel } from '@/utils/ai-quota';
import {
    createMembershipStore,
    formatDate,
    formatRupiah,
    isMembershipActive,
    purchaseKey,
} from '@/utils/membership';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertCircle,
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Crown,
    Loader2,
    RefreshCw,
    ShieldCheck,
    Sparkles,
    Wallet,
} from 'lucide-vue-next';

const auth = useAuthStore();

const loading = ref(true);
const loadError = ref('');
const membership = ref(null);
const wallet = ref(null);
const walletError = ref('');
const confirmOpen = ref(false);
const submitting = ref(false);
const result = ref(null);
const pendingKey = ref(null);

let keyStore = null;

const keyStoreForUser = () => {
    if (!keyStore) {
        keyStore = createMembershipStore(sessionStorage, auth.user?.id ?? 'session');
    }

    return keyStore;
};

const config = computed(() => membership.value?.config ?? null);
const status = computed(() => membership.value?.status ?? null);
const usage = computed(() => membership.value?.usage ?? null);
const price = computed(() => Number(config.value?.price) || 0);
const durationDays = computed(() => Number(config.value?.duration_days) || 0);
const aiDailyLimit = computed(() => Number(config.value?.ai_daily_limit) || 0);
const balance = computed(() => Number(wallet.value?.balance) || 0);
const isActive = computed(() => isMembershipActive(status.value));
const canAfford = computed(() => wallet.value !== null && balance.value >= price.value);
const shortfall = computed(() => Math.max(0, price.value - balance.value));
const remainingAfter = computed(() => Math.max(0, balance.value - price.value));
const quotaText = computed(() => (isActive.value ? quotaLabel(usage.value) : null));

const purchaseBlockedReason = computed(() => {
    if (!config.value?.enabled) return 'Pembelian membership sedang dinonaktifkan.';
    if (!membership.value?.can_purchase) return 'Pembelian membership hanya tersedia untuk akun klien.';
    if (walletError.value) return walletError.value;
    if (!canAfford.value) return `Saldo wallet kurang ${formatRupiah(shortfall.value)}.`;
    if (result.value?.type === 'warning') return 'Cek status membership sebelum membeli lagi.';
    return '';
});

const actionLabel = computed(() => (isActive.value ? 'Perpanjang membership' : 'Beli membership'));

const errorMessage = (err) => {
    const httpStatus = err.response?.status;
    const server = err.response?.data?.message;

    if (httpStatus === 401) return 'Sesi Anda berakhir. Silakan masuk kembali.';
    if (httpStatus === 403) return server || 'Akun ini tidak dapat membeli membership.';
    if (httpStatus === 422) return server || 'Permintaan pembelian tidak dapat diproses.';
    if (httpStatus === 429) return 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
    if (httpStatus >= 500) return 'Server gagal memproses pembelian. Coba lagi.';

    return 'Koneksi terputus. Coba lagi; transaksi yang sama tidak akan dipotong dua kali.';
};

const loadErrorMessage = (err) => {
    const httpStatus = err.response?.status;

    if (httpStatus === 401) return 'Sesi Anda berakhir. Silakan masuk kembali.';
    if (httpStatus === 403) return err.response?.data?.message || 'Akun ini tidak memiliki akses ke halaman membership.';

    return 'Gagal memuat informasi membership. Periksa koneksi lalu coba lagi.';
};

const fetchMembership = async () => {
    const response = await api.get('/rci/membership');
    membership.value = response.data.data;
};

const fetchWallet = async () => {
    try {
        const response = await api.get('/rci/wallet');
        wallet.value = response.data.data;
        walletError.value = '';
    } catch (err) {
        wallet.value = null;
        walletError.value = 'Saldo wallet tidak dapat dimuat.';
    }
};

const load = async () => {
    loading.value = true;
    loadError.value = '';

    try {
        await fetchMembership();
    } catch (err) {
        loadError.value = loadErrorMessage(err);
        loading.value = false;
        return;
    }

    await fetchWallet();
    loading.value = false;
};

const refreshAfterPurchase = async () => {
    try {
        await fetchMembership();
    } catch {
        return false;
    }

    await fetchWallet();

    return isActive.value;
};

const openConfirm = () => {
    if (submitting.value || purchaseBlockedReason.value) return;

    result.value = null;
    confirmOpen.value = true;
};

const confirmPurchase = async () => {
    if (submitting.value) return;

    submitting.value = true;
    result.value = null;

    try {
        pendingKey.value = pendingKey.value || purchaseKey(keyStoreForUser());

        await api.post('/rci/upgrade', {}, {
            headers: { 'Idempotency-Key': pendingKey.value },
        });

        // The server confirmed the transaction, so this key must not be reused
        // for a later intentional renewal.
        keyStoreForUser().clear();
        pendingKey.value = null;
        confirmOpen.value = false;

        if (await refreshAfterPurchase()) {
            result.value = {
                type: 'success',
                title: 'Membership aktif',
                message: status.value?.ends_at
                    ? `Berlaku sampai ${formatDate(status.value.ends_at)}.`
                    : 'Status membership sudah diperbarui dari server.',
            };
        } else {
            result.value = {
                type: 'warning',
                title: 'Status belum terverifikasi',
                message: 'Pembelian tercatat, tetapi status membership belum dapat dipastikan. Cek ulang sebelum memakai fitur AI.',
                retry: true,
            };
        }
    } catch (err) {
        confirmOpen.value = false;
        result.value = {
            type: 'error',
            title: 'Pembelian gagal',
            message: errorMessage(err),
        };

        if (err.response?.status === 422) {
            await fetchWallet();
        }
    } finally {
        submitting.value = false;
    }
};

const recheckStatus = async () => {
    submitting.value = true;

    try {
        if (await refreshAfterPurchase()) {
            keyStoreForUser().clear();
            pendingKey.value = null;
            result.value = {
                type: 'success',
                title: 'Membership aktif',
                message: status.value?.ends_at
                    ? `Berlaku sampai ${formatDate(status.value.ends_at)}.`
                    : 'Status membership sudah diperbarui dari server.',
            };
        } else {
            result.value = {
                type: 'warning',
                title: 'Status belum terverifikasi',
                message: 'Server masih melaporkan membership belum aktif. Hubungi dukungan bila saldo sudah terpotong.',
            };
        }
    } finally {
        submitting.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] py-8 px-4 sm:px-8">
        <div class="max-w-6xl mx-auto">
            <header class="mb-8">
                <Badge variant="outline" class="mb-3 bg-white text-[#070607] border-black/20 font-medium">
                    Membership
                </Badge>
                <h1 class="font-display text-3xl sm:text-5xl font-bold tracking-tight text-[#070607]">
                    AKSES <span class="text-[#fc5000]">PENUH</span> RCI
                </h1>
                <p class="text-sm sm:text-base text-black/60 mt-2 max-w-2xl">
                    Satu paket untuk membuka Konsultasi AI. Harga, durasi, dan kuota mengikuti konfigurasi server.
                </p>
            </header>

            <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-[1.35fr_1fr] gap-5" aria-busy="true" aria-label="Memuat informasi membership">
                <div class="bg-[#f7f6f2] border border-black/10 rounded-[28px] p-6 sm:p-8 space-y-5">
                    <div class="h-4 w-24 bg-black/10 rounded animate-pulse" />
                    <div class="h-12 w-48 bg-black/10 rounded animate-pulse" />
                    <div class="space-y-3 pt-4">
                        <div v-for="i in 4" :key="i" class="h-4 w-full max-w-sm bg-black/5 rounded animate-pulse" />
                    </div>
                </div>
                <div class="space-y-5">
                    <div class="bg-white border border-black/10 rounded-[28px] p-6 h-36 animate-pulse" />
                    <div class="bg-white border border-black/10 rounded-[28px] p-6 h-44 animate-pulse" />
                </div>
            </div>

            <div v-else-if="loadError" class="bg-white border border-black/10 rounded-[28px] p-8 sm:p-12 text-center" role="alert">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                    <AlertCircle class="w-6 h-6" />
                </div>
                <h2 class="font-display text-xl font-bold text-[#070607]">Informasi membership gagal dimuat</h2>
                <p class="text-sm text-black/60 mt-2 max-w-md mx-auto">{{ loadError }}</p>
                <Button variant="outline" class="mt-5 gap-2 min-h-[44px]" @click="load">
                    <RefreshCw class="w-4 h-4" />
                    Coba lagi
                </Button>
            </div>

            <template v-else>
                <div class="grid grid-cols-1 lg:grid-cols-[1.35fr_1fr] gap-5 items-start">
                    <section class="bg-[#f7f6f2] border border-black/10 rounded-[28px] p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-black/50">Paket Pro</p>
                                <p class="font-display text-4xl sm:text-5xl font-bold text-[#070607] mt-2">
                                    {{ formatRupiah(price) }}
                                </p>
                                <p class="text-sm text-black/60 mt-1">untuk {{ durationDays }} hari</p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-[#fc5000] text-white flex items-center justify-center shrink-0">
                                <Crown class="w-5 h-5" />
                            </div>
                        </div>

                        <p class="text-sm text-black/70 mt-5 max-w-md">
                            Masa aktif dimulai saat pembelian, atau dilanjutkan dari sisa masa aktif bila membership masih berjalan.
                        </p>

                        <ul class="mt-6 space-y-3 text-sm text-black/75">
                            <li class="flex items-start gap-2.5">
                                <Sparkles class="w-4 h-4 text-[#fc5000] mt-0.5 shrink-0" />
                                <span>{{ aiDailyLimit }} pertanyaan AI per hari, direset pukul 00.00 WIB.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <ShieldCheck class="w-4 h-4 text-[#fc5000] mt-0.5 shrink-0" />
                                <span>Akses Konsultasi AI untuk akun ini, bukan untuk akun lain.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <Wallet class="w-4 h-4 text-[#fc5000] mt-0.5 shrink-0" />
                                <span>Pembayaran dipotong sekali dari saldo wallet.</span>
                            </li>
                        </ul>

                        <p class="text-xs text-black/50 mt-6">
                            Harga dan durasi diambil dari server saat transaksi. Angka di halaman ini tidak dikirim sebagai nilai pembayaran.
                        </p>
                    </section>

                    <div class="space-y-5">
                        <section class="bg-white border border-black/10 rounded-[28px] p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-display text-lg font-bold text-[#070607]">Status membership</h2>
                                <Badge :variant="isActive ? 'success' : 'outline'">
                                    {{ isActive ? 'Aktif' : 'Belum aktif' }}
                                </Badge>
                            </div>

                            <div v-if="isActive" class="mt-4 space-y-2 text-sm text-black/70">
                                <p class="flex items-center gap-2">
                                    <CalendarDays class="w-4 h-4 text-[#fc5000]" />
                                    <span>Berlaku sampai {{ formatDate(status.ends_at) }}</span>
                                </p>
                                <p v-if="status.days_left != null" class="text-black/60">
                                    Sisa {{ status.days_left }} hari.
                                </p>
                                <p v-if="quotaText" class="text-[#fc5000] font-semibold">
                                    {{ quotaText }}
                                </p>
                            </div>

                            <p v-else class="mt-4 text-sm text-black/60">
                                Konsultasi AI terkunci sampai membership aktif.
                            </p>
                        </section>

                        <section class="bg-white border border-black/10 rounded-[28px] p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-display text-lg font-bold text-[#070607]">Saldo wallet</h2>
                                <Wallet class="w-5 h-5 text-[#524ae9]" />
                            </div>

                            <div v-if="walletError" class="mt-4 text-sm text-red-600" role="alert">
                                <p>{{ walletError }}</p>
                                <Button variant="outline" size="sm" class="mt-3 gap-2 min-h-[44px]" @click="fetchWallet">
                                    <RefreshCw class="w-4 h-4" />
                                    Muat saldo
                                </Button>
                            </div>

                            <template v-else>
                                <p class="font-display text-3xl font-bold text-[#070607] mt-3">
                                    {{ formatRupiah(balance) }}
                                </p>

                                <p v-if="canAfford" class="text-sm text-emerald-700 mt-2">
                                    Cukup. Sisa setelah pembelian {{ formatRupiah(remainingAfter) }}.
                                </p>
                                <div v-else class="mt-2">
                                    <p class="text-sm text-red-600">
                                        Kurang {{ formatRupiah(shortfall) }} dari harga membership.
                                    </p>
                                    <a href="/client/wallet" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#fc5000] underline underline-offset-4 mt-2 min-h-[44px]">
                                        Top up saldo wallet
                                        <ArrowRight class="w-4 h-4" />
                                    </a>
                                </div>
                            </template>
                        </section>

                        <section class="bg-[#070607] rounded-[28px] p-6 text-white">
                            <Button
                                class="w-full min-h-[52px] bg-[#fc5000] hover:bg-[#e04700] text-white font-semibold rounded-full"
                                :disabled="submitting || Boolean(purchaseBlockedReason)"
                                @click="openConfirm"
                            >
                                <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                                <Crown v-else class="w-4 h-4" />
                                {{ submitting ? 'Memproses...' : actionLabel }}
                            </Button>

                            <p v-if="purchaseBlockedReason" class="text-xs text-white/70 mt-3 text-center">
                                {{ purchaseBlockedReason }}
                            </p>
                            <p v-else class="text-xs text-white/60 mt-3 text-center">
                                Konfirmasi harga dan durasi sebelum saldo dipotong.
                            </p>
                        </section>
                    </div>
                </div>

                <div v-if="result" class="mt-5" aria-live="polite">
                    <Alert :variant="result.type === 'success' ? 'success' : result.type === 'warning' ? 'ember' : 'destructive'">
                        <CheckCircle2 v-if="result.type === 'success'" class="w-4 h-4" />
                        <AlertCircle v-else class="w-4 h-4" />
                        <AlertTitle>{{ result.title }}</AlertTitle>
                        <AlertDescription>
                            <p>{{ result.message }}</p>
                            <Button
                                v-if="result.retry"
                                variant="outline"
                                size="sm"
                                class="mt-3 gap-2 min-h-[44px]"
                                :disabled="submitting"
                                @click="recheckStatus"
                            >
                                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': submitting }" />
                                Cek status membership
                            </Button>
                        </AlertDescription>
                    </Alert>
                </div>
            </template>
        </div>

        <Dialog :open="confirmOpen" @update:open="confirmOpen = submitting ? confirmOpen : $event">
            <DialogContent class="sm:max-w-md" :show-close-button="false">
                <DialogHeader>
                    <DialogTitle class="font-display text-xl font-bold">
                        {{ isActive ? 'Perpanjang membership?' : 'Beli membership?' }}
                    </DialogTitle>
                    <DialogDescription>
                        Saldo wallet dipotong {{ formatRupiah(price) }} untuk {{ durationDays }} hari membership.
                        <span v-if="isActive"> Masa aktif baru dilanjutkan dari tanggal berakhir saat ini.</span>
                    </DialogDescription>
                </DialogHeader>

                <div class="rounded-2xl bg-[#f7f6f2] border border-black/10 p-4 text-sm text-black/70">
                    <p class="flex items-center justify-between gap-3">
                        <span>Saldo saat ini</span>
                        <span class="font-semibold text-[#070607]">{{ formatRupiah(balance) }}</span>
                    </p>
                    <p class="flex items-center justify-between gap-3 mt-2">
                        <span>Setelah pembelian</span>
                        <span class="font-semibold text-[#070607]">{{ formatRupiah(remainingAfter) }}</span>
                    </p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-2">
                    <Button variant="outline" class="min-h-[44px]" :disabled="submitting" @click="confirmOpen = false">
                        Batal
                    </Button>
                    <Button
                        class="min-h-[44px] bg-[#fc5000] hover:bg-[#e04700] text-white"
                        :disabled="submitting"
                        @click="confirmPurchase"
                    >
                        <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                        {{ submitting ? 'Memproses...' : 'Konfirmasi pembelian' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
