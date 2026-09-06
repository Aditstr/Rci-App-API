<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Sparkles, 
    Scale, 
    Users, 
    ShieldCheck, 
    ArrowRight, 
    CheckCircle2, 
    Clock, 
    FileText 
} from 'lucide-vue-next';

const router = useRouter();
const quickPrompt = ref('');

const startAiChat = () => {
    if (quickPrompt.value.trim()) {
        router.push({ path: '/client/ai-chat', query: { q: quickPrompt.value } });
    } else {
        router.push('/client/ai-chat');
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#e2e2df] text-[#070607]">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-8 pt-12 pb-20">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Left Column -->
                <div class="lg:col-span-7 flex flex-col items-start gap-6">
                    <Badge variant="secondary" class="gap-2 px-4 py-1.5 text-xs uppercase tracking-wider font-bold">
                        <Sparkles class="w-3.5 h-3.5 text-[#fc5000]" />
                        Platform Konsultasi Hukum Cerdas #1 di Indonesia
                    </Badge>

                    <h1 class="font-display text-5xl sm:text-7xl font-bold tracking-tight text-[#070607] leading-[0.95]">
                        KONSULTASI HUKUM CEPAT, <span class="text-[#fc5000]">TEPAT</span> & TERPERCAYA.
                    </h1>

                    <p class="text-base sm:text-lg text-black/70 max-w-xl leading-relaxed">
                        Roys Counsel Indonesia (RCI) menghubungkan Anda dengan kecerdasan buatan hukum, Paralegal berdedikasi, dan Advokat profesional bersertifikasi dalam satu ekosistem terpadu.
                    </p>

                    <!-- Quick Prompt Box -->
                    <div class="w-full max-w-lg bg-[#f7f6f2] p-2 sm:p-3 rounded-full border border-black/10 shadow-md flex items-center gap-2">
                        <input
                            v-model="quickPrompt"
                            @keyup.enter="startAiChat"
                            type="text"
                            placeholder="Tanyakan masalah hukum Anda... (cth: wanprestasi kontrak)"
                            class="flex-1 bg-transparent px-4 py-2 text-sm text-[#070607] placeholder:text-black/40 outline-none"
                        />
                        <Button @click="startAiChat" class="gap-2 shrink-0">
                            Tanya AI
                            <ArrowRight class="w-4 h-4" />
                        </Button>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm text-black/60 pt-2">
                        <div class="flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                            <span>3 Pertanyaan Gratis Tiap Hari</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="w-4 h-4 text-[#fc5000]" />
                            <span>Privasi & Kerahasiaan Terjamin</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Interactive Card -->
                <div class="lg:col-span-5">
                    <Card class="bg-[#070607] text-white border-0 shadow-2xl p-6 sm:p-8 rounded-[40px] relative overflow-hidden">
                        <!-- Glow effect -->
                        <div class="absolute -right-20 -top-20 w-64 h-64 bg-[#fc5000]/30 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-[#fc5000] flex items-center justify-center text-white">
                                    <Scale class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-display text-lg font-bold">RCI AI Legal Copilot</h4>
                                    <p class="text-xs text-white/50">Online & Siap Membantu 24/7</p>
                                </div>
                            </div>
                            <Badge class="bg-emerald-500/20 text-emerald-400 border-emerald-500/30">
                                Aktif
                            </Badge>
                        </div>

                        <!-- Chat preview snippet -->
                        <div class="space-y-4 mb-6">
                            <div class="bg-white/10 p-4 rounded-2xl text-xs sm:text-sm text-white/90">
                                <p class="font-semibold text-[#fc5000] mb-1">Pertanyaan Anda:</p>
                                <p>"Bagaimana prosedur hukum jika rekan bisnis terlambat membayar tagihan proyek lebih dari 90 hari?"</p>
                            </div>
                            <div class="bg-white/5 border border-white/10 p-4 rounded-2xl text-xs sm:text-sm text-white/80 space-y-2">
                                <p class="font-semibold text-emerald-400">Analisis Hukum Awal RCI:</p>
                                <p class="text-xs text-white/70">
                                    1. Periksa klausul wanprestasi pada surat perjanjian / SPK.<br>
                                    2. Terbitkan Somasi Tertulis I, II, dan III secara resmi.<br>
                                    3. Anda dapat mengajukan mediasi atau gugatan perdata ke Pengadilan Negeri.
                                </p>
                            </div>
                        </div>

                        <router-link to="/client/ai-chat" class="block w-full">
                            <Button class="w-full bg-[#fc5000] hover:bg-[#e04700] text-white py-6 rounded-full font-bold">
                                Coba Konsultasi Sekarang
                            </Button>
                        </router-link>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="layanan" class="bg-[#f7f6f2] py-20 border-t border-b border-black/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <Badge variant="secondary" class="mb-3">Ekosistem 3 Tingkat</Badge>
                    <h2 class="font-display text-4xl sm:text-5xl font-bold tracking-tight text-[#070607]">
                        SOLUSI HUKUM FLEKSIBEL SESUAI KEBUTUHAN
                    </h2>
                    <p class="text-black/60 mt-4 text-sm sm:text-base">
                        Mulai dari konsultasi mandiri berbasis AI hingga pendampingan perkara penuh oleh pengacara spesialis.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Tier 1 -->
                    <Card class="p-8 flex flex-col justify-between hover:translate-y-[-4px] transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-[#fc5000]/10 text-[#fc5000] flex items-center justify-center mb-6">
                                <Sparkles class="w-6 h-6" />
                            </div>
                            <h3 class="font-display text-2xl font-bold mb-2">Konsultasi AI Cerdas</h3>
                            <p class="text-sm text-black/60 mb-6 leading-relaxed">
                                Dapatkan analisis hukum awal dalam hitungan detik. Cocok untuk riset pasal, pemahaman kontrak sederhana, dan tanya jawab hukum harian.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-black/70 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>Tersedia 24/7 tanpa antre</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>Referensi peraturan perundang-undangan</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>3 sesi gratis per hari</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client/ai-chat">
                            <Button variant="outline" class="w-full">
                                Mulai Chat AI
                            </Button>
                        </router-link>
                    </Card>

                    <!-- Tier 2 -->
                    <Card class="p-8 flex flex-col justify-between border-2 border-[#fc5000] bg-white relative hover:translate-y-[-4px] transition-all shadow-xl">
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <Badge class="bg-[#fc5000] text-white uppercase text-[10px] tracking-widest px-4">
                                Paling Populer
                            </Badge>
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-[#fc5000] text-white flex items-center justify-center mb-6">
                                <Users class="w-6 h-6" />
                            </div>
                            <h3 class="font-display text-2xl font-bold mb-2">Bantuan Paralegal</h3>
                            <p class="text-sm text-black/60 mb-6 leading-relaxed">
                                Penelaahan dokumen hukum, pembuatan draf somasi, dan penyusunan berkas legalitas dibantu langsung oleh praktisi hukum berpengalaman.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-black/70 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>Review draf kontrak & perjanjian</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>Pembuatan surat somasi & tanggapan</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000]" />
                                    <span>Biaya transparan & escrow aman</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client">
                            <Button variant="default" class="w-full">
                                Buka Kasus Paralegal
                            </Button>
                        </router-link>
                    </Card>

                    <!-- Tier 3 -->
                    <Card class="p-8 flex flex-col justify-between hover:translate-y-[-4px] transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-[#524ae9]/10 text-[#524ae9] flex items-center justify-center mb-6">
                                <Scale class="w-6 h-6" />
                            </div>
                            <h3 class="font-display text-2xl font-bold mb-2">Advokat Profesional</h3>
                            <p class="text-sm text-black/60 mb-6 leading-relaxed">
                                Pendampingan litigasi di persidangan atau negosiasi sengketa strategis oleh Advokat berizin PERADI dengan spesialisasi kasus terverifikasi.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-black/70 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#524ae9]" />
                                    <span>Pendampingan sidang & kepolisian</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#524ae9]" />
                                    <span>Konsultasi tatap muka / video call</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#524ae9]" />
                                    <span>Surat kuasa khusus & eksekusi putusan</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client">
                            <Button variant="outline" class="w-full">
                                Konsultasi Advokat
                            </Button>
                        </router-link>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#070607] text-white py-12 px-4 sm:px-8">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display font-bold">
                        R
                    </div>
                    <span class="font-display text-xl font-bold">RCI — Roys Counsel Indonesia</span>
                </div>
                <p class="text-xs text-white/50 text-center sm:text-right">
                    © 2026 Roys Counsel Indonesia. Dibangun dengan Vue 3, Vite & Shadcn UI.
                </p>
            </div>
        </footer>
    </div>
</template>
