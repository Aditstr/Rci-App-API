<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    ArrowLeft,
    Send,
    FileText,
    Scale,
    Loader2,
    AlertCircle,
    CheckCircle2,
    Wallet,
    Info,
    ChevronDown
} from 'lucide-vue-next';

const router = useRouter();

const form = ref({
    title: '',
    category: '',
    description: '',
});

const submitting = ref(false);
const error = ref(null);
const fieldErrors = ref({});
const showCategoryDropdown = ref(false);

const categories = [
    { value: 'perdata', label: 'Hukum Perdata', desc: 'Sengketa kontrak, wanprestasi, ganti rugi' },
    { value: 'pidana', label: 'Hukum Pidana', desc: 'Penipuan, penggelapan, kekerasan' },
    { value: 'keluarga', label: 'Hukum Keluarga', desc: 'Perceraian, hak asuh anak, waris' },
    { value: 'perusahaan', label: 'Hukum Perusahaan', desc: 'Pendirian PT, kontrak bisnis, M&A' },
    { value: 'properti', label: 'Hukum Properti', desc: 'Sertifikat tanah, sengketa lahan' },
    { value: 'ketenagakerjaan', label: 'Ketenagakerjaan', desc: 'PHK, kontrak kerja, BPJS' },
    { value: 'imigrasi', label: 'Imigrasi', desc: 'KITAS, visa kerja, izin tinggal' },
    { value: 'kekayaan intelektual', label: 'Kekayaan Intelektual', desc: 'Merek dagang, hak cipta, paten' },
    { value: 'pajak', label: 'Hukum Pajak', desc: 'Sengketa pajak, keberatan, banding' },
];

const selectedCategoryLabel = computed(() => {
    const found = categories.find(c => c.value === form.value.category);
    return found ? found.label : '';
});

const charCount = computed(() => form.value.description.length);
const isFormValid = computed(() => {
    return form.value.title.trim() && form.value.category && form.value.description.trim();
});

const selectCategory = (value) => {
    form.value.category = value;
    showCategoryDropdown.value = false;
};

const handleSubmit = async () => {
    if (!isFormValid.value || submitting.value) return;

    error.value = null;
    fieldErrors.value = {};
    submitting.value = true;

    try {
        const response = await api.post('/cases', {
            title: form.value.title.trim(),
            category: form.value.category,
            description: form.value.description.trim(),
        });

        if (response.data.success) {
            const caseId = response.data.data?.id;
            router.push(caseId ? `/client/cases/${caseId}` : '/client');
        }
    } catch (err) {
        if (err.response?.status === 422) {
            fieldErrors.value = err.response.data.errors || {};
            error.value = err.response.data.message || 'Data formulir tidak valid.';
        } else if (err.response?.status === 400) {
            error.value = err.response.data.message || 'Saldo tidak mencukupi.';
        } else {
            error.value = err.response?.data?.message || 'Gagal mengajukan kasus. Silakan coba lagi.';
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] py-8 px-4 sm:px-8">
        <div class="max-w-3xl mx-auto space-y-6">

            <!-- Back button -->
            <button
                @click="router.push('/client')"
                class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition-colors"
            >
                <ArrowLeft class="w-4 h-4" />
                Kembali ke Dashboard
            </button>

            <!-- Page Header -->
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Ajukan Kasus Baru
                </h1>
                <p class="text-sm text-slate-500">
                    Deskripsikan permasalahan hukum Anda. Paralegal terverifikasi akan meninjau dan menangani kasus ini.
                </p>
            </div>

            <!-- Fee Notice -->
            <Alert class="bg-amber-50 border-amber-200">
                <Wallet class="w-4 h-4 text-amber-600" />
                <AlertDescription class="text-amber-800 text-sm">
                    <strong>Biaya pendaftaran: Rp 20.000</strong> — akan dipotong otomatis dari saldo dompet RCI Anda sebagai dana escrow terlindungi.
                </AlertDescription>
            </Alert>

            <!-- Error Alert -->
            <Alert v-if="error" variant="destructive">
                <AlertCircle class="w-4 h-4" />
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Form Card -->
            <Card class="border border-slate-200 shadow-sm">
                <CardContent class="p-6 sm:p-8 space-y-6">
                    <form @submit.prevent="handleSubmit" class="space-y-6">

                        <!-- Title -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Judul Kasus <span class="text-red-500">*</span>
                            </label>
                            <Input
                                v-model="form.title"
                                placeholder="cth: Sengketa kontrak kerjasama CV Maju Bersama"
                                maxlength="255"
                                class="bg-white"
                                :class="{ 'border-red-400': fieldErrors.title }"
                            />
                            <p v-if="fieldErrors.title" class="text-xs text-red-500">
                                {{ fieldErrors.title[0] }}
                            </p>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Kategori Hukum <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <button
                                    type="button"
                                    @click="showCategoryDropdown = !showCategoryDropdown"
                                    class="w-full flex items-center justify-between bg-white border rounded-md px-3 py-2 text-sm text-left transition-colors"
                                    :class="[
                                        fieldErrors.category ? 'border-red-400' : 'border-slate-200 hover:border-slate-300',
                                        form.category ? 'text-slate-900' : 'text-slate-400'
                                    ]"
                                >
                                    <span>{{ selectedCategoryLabel || 'Pilih kategori hukum...' }}</span>
                                    <ChevronDown class="w-4 h-4 text-slate-400 shrink-0" />
                                </button>

                                <!-- Dropdown -->
                                <div
                                    v-if="showCategoryDropdown"
                                    class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-y-auto"
                                >
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.value"
                                        type="button"
                                        @click="selectCategory(cat.value)"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0"
                                        :class="{ 'bg-orange-50': form.category === cat.value }"
                                    >
                                        <div class="text-sm font-medium text-slate-900">{{ cat.label }}</div>
                                        <div class="text-xs text-slate-500">{{ cat.desc }}</div>
                                    </button>
                                </div>
                            </div>
                            <p v-if="fieldErrors.category" class="text-xs text-red-500">
                                {{ fieldErrors.category[0] }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Deskripsi Kasus <span class="text-red-500">*</span>
                            </label>
                            <Textarea
                                v-model="form.description"
                                placeholder="Jelaskan kronologi permasalahan hukum Anda secara detail. Sertakan tanggal kejadian, pihak-pihak yang terlibat, dan dokumen terkait jika ada."
                                rows="8"
                                maxlength="10000"
                                class="bg-white resize-none"
                                :class="{ 'border-red-400': fieldErrors.description }"
                            />
                            <div class="flex items-center justify-between">
                                <p v-if="fieldErrors.description" class="text-xs text-red-500">
                                    {{ fieldErrors.description[0] }}
                                </p>
                                <span class="text-xs text-slate-400 ml-auto">
                                    {{ charCount.toLocaleString() }} / 10.000 karakter
                                </span>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex gap-3">
                            <Info class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" />
                            <div class="text-xs text-slate-600 space-y-1">
                                <p><strong>Apa yang terjadi setelah pengajuan?</strong></p>
                                <ol class="list-decimal pl-4 space-y-0.5">
                                    <li>Kasus Anda masuk ke marketplace dan ditinjau oleh Paralegal terverifikasi.</li>
                                    <li>Paralegal akan mengambil kasus Anda dan memulai analisis.</li>
                                    <li>Jika diperlukan, kasus akan dieskalasi ke Advokat profesional.</li>
                                    <li>Anda dapat berkomunikasi langsung via chat room kasus.</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <Button
                                type="submit"
                                :disabled="!isFormValid || submitting"
                                class="bg-[#fc5000] hover:bg-[#e04700] text-white rounded-xl font-medium px-8 h-12 gap-2 flex-1 sm:flex-none"
                            >
                                <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                                <Send v-else class="w-4 h-4" />
                                {{ submitting ? 'Mengirim...' : 'Ajukan Kasus (Rp 20.000)' }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.push('/client')"
                                class="rounded-xl h-12"
                            >
                                Batal
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
