<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, 
    Sparkles, 
    FolderGit2, 
    Wallet, 
    Bell, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    FileText,
    ArrowUpRight
} from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const walletBalance = ref(0);
const cases = ref([]);
const stats = ref({
    active: 0,
    completed: 0,
    total: 0,
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const fetchDashboardData = async () => {
    loading.value = true;
    try {
        // Fetch wallet
        try {
            const walletRes = await api.get('/rci/wallet');
            walletBalance.value = walletRes.data.data?.balance || walletRes.data.balance || 0;
        } catch (e) {
            // fallback if wallet empty
            walletBalance.value = 0;
        }

        // Fetch cases
        try {
            const casesRes = await api.get('/cases');
            const caseList = casesRes.data.data || casesRes.data || [];
            cases.value = Array.isArray(caseList) ? caseList : [];

            stats.value.total = cases.value.length;
            stats.value.active = cases.value.filter(c => ['open', 'in_progress', 'quoted', 'assigned'].includes(c.status)).length;
            stats.value.completed = cases.value.filter(c => c.status === 'completed').length;
        } catch (e) {
            cases.value = [];
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <div class="min-h-screen bg-[#e2e2df] py-8 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header greeting -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#f7f6f2] p-6 sm:p-8 rounded-[36px] border border-black/10 shadow-sm">
                <div>
                    <Badge variant="secondary" class="mb-2">Dashboard Klien</Badge>
                    <h1 class="font-display text-3xl sm:text-5xl font-bold tracking-tight text-[#070607]">
                        SELAMAT DATANG, <span class="text-[#fc5000]">{{ auth.userName.toUpperCase() }}</span>
                    </h1>
                    <p class="text-sm text-black/60 mt-1">
                        Kelola konsultasi hukum, lacak kasus, dan saldo escrow Anda di sini.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <router-link to="/client/ai-chat">
                        <Button variant="outline" class="gap-2 rounded-full border-black/20">
                            <Sparkles class="w-4 h-4 text-[#fc5000]" />
                            Chat AI
                        </Button>
                    </router-link>
                    <Button class="gap-2 rounded-full">
                        <Plus class="w-4 h-4" />
                        Buat Kasus Baru
                    </Button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-6 bg-[#fc5000] text-white border-0 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-wider font-semibold text-white/80">Kasus Aktif</span>
                        <Clock class="w-5 h-5 text-white/80" />
                    </div>
                    <div class="font-display text-4xl font-bold">
                        {{ stats.active }}
                    </div>
                    <p class="text-xs text-white/70 mt-1">Dalam proses penanganan</p>
                </Card>

                <Card class="p-6 bg-[#f7f6f2] border-black/10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-wider font-semibold text-black/60">Kasus Selesai</span>
                        <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                    </div>
                    <div class="font-display text-4xl font-bold text-[#070607]">
                        {{ stats.completed }}
                    </div>
                    <p class="text-xs text-black/50 mt-1">Telah diselesaikan tuntas</p>
                </Card>

                <Card class="p-6 bg-[#524ae9] text-white border-0 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-wider font-semibold text-white/80">Saldo Dompet RCI</span>
                        <Wallet class="w-5 h-5 text-white/80" />
                    </div>
                    <div class="font-display text-3xl font-bold">
                        {{ formatCurrency(walletBalance) }}
                    </div>
                    <p class="text-xs text-white/70 mt-1">Dana escrow terlindungi</p>
                </Card>

                <Card class="p-6 bg-[#f7f6f2] border-black/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs uppercase tracking-wider font-semibold text-black/60">Konsultasi AI</span>
                            <Sparkles class="w-4 h-4 text-[#fc5000]" />
                        </div>
                        <p class="text-xs text-black/60">Gunakan copilot hukum berbasis regulasi Indonesia.</p>
                    </div>
                    <router-link to="/client/ai-chat" class="mt-4">
                        <Button variant="outline" size="sm" class="w-full gap-1 text-xs">
                            Buka Chatbot AI
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Button>
                    </router-link>
                </Card>
            </div>

            <!-- Cases Table Section -->
            <Card class="bg-[#f7f6f2] p-6 sm:p-8 rounded-[36px] border-black/10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-display text-2xl font-bold text-[#070607]">Daftar Kasus Anda</h3>
                        <p class="text-xs text-black/60">Status penanganan dan progres komunikasi dengan ahli hukum.</p>
                    </div>
                </div>

                <div v-if="loading" class="py-12 text-center text-sm text-black/50">
                    Memuat data kasus...
                </div>

                <div v-else-if="cases.length === 0" class="py-12 text-center space-y-4">
                    <div class="w-12 h-12 rounded-full bg-black/5 text-black/40 flex items-center justify-center mx-auto">
                        <FolderGit2 class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="font-display text-lg font-bold text-[#070607]">Belum Ada Kasus</h4>
                        <p class="text-xs text-black/50 max-w-sm mx-auto mt-1">
                            Anda belum mendaftarkan kasus hukum apapun. Mulai konsultasi gratis dengan AI atau ajukan permohonan ke Paralegal & Advokat.
                        </p>
                    </div>
                    <div class="pt-2">
                        <router-link to="/client/ai-chat">
                            <Button variant="default" size="sm" class="gap-2">
                                <Sparkles class="w-4 h-4" />
                                Mulai Konsultasi AI
                            </Button>
                        </router-link>
                    </div>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-black/10 text-xs font-semibold uppercase text-black/50">
                            <tr>
                                <th class="pb-3">Judul Kasus</th>
                                <th class="pb-3">Kategori</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Tanggal</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5">
                            <tr v-for="c in cases" :key="c.id" class="hover:bg-black/[0.02]">
                                <td class="py-4 font-semibold text-[#070607]">{{ c.title || 'Kasus Hukum' }}</td>
                                <td class="py-4 text-black/70">{{ c.category || 'Perdata' }}</td>
                                <td class="py-4">
                                    <Badge :variant="c.status === 'completed' ? 'success' : 'default'">
                                        {{ c.status }}
                                    </Badge>
                                </td>
                                <td class="py-4 text-black/50 text-xs">{{ c.created_at ? new Date(c.created_at).toLocaleDateString('id-ID') : '-' }}</td>
                                <td class="py-4 text-right">
                                    <Button variant="ghost" size="sm">Detail</Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </div>
</template>
