<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
    Sparkles, 
    Scale, 
    Users, 
    ShieldCheck, 
    ArrowRight, 
    CheckCircle2, 
    Search,
    Bot,
    User,
    Clock,
    FileCheck
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
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-12 pb-20 sm:pt-16 sm:pb-24">
            <!-- Subtle background accent blur -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-orange-100/60 to-amber-50/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    <!-- Left Column -->
                    <div class="lg:col-span-7 flex flex-col items-start gap-6">
                        <!-- Pill Badge -->
                        <div class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50/90 px-3.5 py-1 text-xs font-semibold text-orange-800 shadow-2xs">
                            <Sparkles class="w-3.5 h-3.5 text-[#fc5000]" />
                            <span>Platform Konsultasi Hukum Cerdas #1 di Indonesia</span>
                        </div>

                        <!-- Main Heading -->
                        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-950 leading-[1.12]">
                            Konsultasi Hukum <br />
                            <span class="bg-gradient-to-r from-[#fc5000] via-orange-500 to-amber-500 bg-clip-text text-transparent">
                                Cepat, Tepat & Terpercaya.
                            </span>
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-base sm:text-lg text-slate-600 max-w-xl leading-relaxed">
                            Roys Counsel Indonesia (RCI) menghubungkan Anda dengan kecerdasan buatan hukum, Paralegal berdedikasi, dan Advokat profesional bersertifikasi dalam satu ekosistem terpadu.
                        </p>

                        <!-- Search/Prompt Box -->
                        <div class="w-full max-w-xl bg-white p-2 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-all flex items-center gap-2">
                            <div class="pl-3 text-slate-400">
                                <Search class="w-4 h-4" />
                            </div>
                            <input
                                v-model="quickPrompt"
                                @keyup.enter="startAiChat"
                                type="text"
                                placeholder="Tanyakan masalah hukum Anda... (cth: wanprestasi kontrak)"
                                class="flex-1 bg-transparent px-2 py-2 text-sm text-slate-800 placeholder:text-slate-400 outline-none"
                            />
                            <Button
                                @click="startAiChat"
                                class="bg-[#fc5000] hover:bg-[#e04700] text-white rounded-xl text-sm font-medium px-5 h-10 gap-2 shrink-0 shadow-xs"
                            >
                                <span>Tanya AI</span>
                                <ArrowRight class="w-4 h-4" />
                            </Button>
                        </div>

                        <!-- Trust bullets -->
                        <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm text-slate-500 pt-1">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>3 Pertanyaan Gratis Tiap Hari</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-[#fc5000] shrink-0" />
                                <span>Privasi & Kerahasiaan Terjamin</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Copilot Preview Card -->
                    <div class="lg:col-span-5">
                        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-6 relative overflow-hidden">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between pb-5 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#fc5000] to-orange-600 flex items-center justify-center text-white shadow-sm">
                                        <Bot class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-slate-900">RCI Legal Copilot</h4>
                                        <p class="text-xs text-slate-400">Online & Siap Membantu 24/7</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            </div>

                            <!-- Chat Preview Body -->
                            <div class="space-y-4 py-5">
                                <!-- User Question Bubble -->
                                <div class="flex gap-2.5 items-start justify-end">
                                    <div class="bg-slate-100 text-slate-800 text-xs rounded-2xl rounded-tr-none px-4 py-3 max-w-[85%] leading-relaxed">
                                        <p class="font-semibold text-slate-600 text-[11px] mb-0.5">Pertanyaan Anda:</p>
                                        "Bagaimana langkah hukum jika rekan bisnis wanprestasi dan tidak membayar tagihan > 90 hari?"
                                    </div>
                                    <Avatar class="w-7 h-7 mt-1">
                                        <AvatarFallback class="bg-slate-800 text-white text-[10px]">U</AvatarFallback>
                                    </Avatar>
                                </div>

                                <!-- AI Response Bubble -->
                                <div class="flex gap-2.5 items-start">
                                    <Avatar class="w-7 h-7 mt-1">
                                        <AvatarFallback class="bg-[#fc5000] text-white text-[10px]">AI</AvatarFallback>
                                    </Avatar>
                                    <div class="bg-orange-50/60 border border-orange-100 text-slate-800 text-xs rounded-2xl rounded-tl-none px-4 py-3 max-w-[90%] leading-relaxed space-y-2">
                                        <p class="font-semibold text-[#fc5000] text-[11px]">Analisis Regulasi Awal RCI:</p>
                                        <ul class="space-y-1 text-slate-700">
                                            <li class="flex items-start gap-1.5">
                                                <span class="text-[#fc5000] font-bold">1.</span>
                                                <span>Verifikasi klausul wanprestasi pada SPK / Perjanjian Kerjasama.</span>
                                            </li>
                                            <li class="flex items-start gap-1.5">
                                                <span class="text-[#fc5000] font-bold">2.</span>
                                                <span>Terbitkan Surat Somasi Resmi ke-1 dengan tenggat waktu jelas.</span>
                                            </li>
                                            <li class="flex items-start gap-1.5">
                                                <span class="text-[#fc5000] font-bold">3.</span>
                                                <span>Ajukan mediasi hukum atau gugatan perdata wanprestasi ke Pengadilan Negeri.</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer CTA -->
                            <div class="pt-2">
                                <router-link to="/client/ai-chat" class="block w-full">
                                    <Button class="w-full bg-[#fc5000] hover:bg-[#e04700] text-white py-5 rounded-xl font-semibold text-sm shadow-sm gap-2">
                                        <span>Coba Konsultasi Gratis Sekarang</span>
                                        <ArrowRight class="w-4 h-4" />
                                    </Button>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="layanan" class="bg-white py-20 border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 mb-3">
                        Ekosistem 3 Tingkat
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-950">
                        Solusi Hukum Fleksibel Sesuai Kebutuhan
                    </h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Mulai dari konsultasi mandiri berbasis AI hingga pendampingan perkara penuh oleh pengacara spesialis.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Tier 1: AI -->
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-8 flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-orange-100 text-[#fc5000] flex items-center justify-center mb-6">
                                <Sparkles class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Konsultasi AI Cerdas</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Dapatkan analisis hukum awal dalam hitungan detik. Cocok untuk riset pasal, pemahaman kontrak sederhana, dan tanya jawab hukum harian.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Tersedia 24/7 tanpa antre</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Referensi peraturan Indonesia</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>3 sesi gratis per hari</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client/ai-chat">
                            <Button variant="outline" class="w-full rounded-xl border-slate-200 font-medium hover:bg-white text-slate-700">
                                Mulai Chat AI
                            </Button>
                        </router-link>
                    </div>

                    <!-- Tier 2: Paralegal (Featured) -->
                    <div class="bg-white rounded-2xl border-2 border-[#fc5000] p-8 flex flex-col justify-between shadow-xl relative hover:translate-y-[-2px] transition-all">
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="bg-[#fc5000] text-white uppercase text-[10px] font-bold tracking-wider px-3.5 py-1 rounded-full shadow-xs">
                                Paling Populer
                            </span>
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-[#fc5000] text-white flex items-center justify-center mb-6 shadow-sm">
                                <Users class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Bantuan Paralegal</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Penelaahan dokumen hukum, pembuatan draf somasi, dan penyusunan berkas legalitas dibantu langsung oleh praktisi hukum berpengalaman.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Review draf kontrak & perjanjian</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Pembuatan surat somasi resmi</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Biaya terjangkau & escrow aman</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client">
                            <Button class="w-full bg-[#fc5000] hover:bg-[#e04700] text-white rounded-xl font-medium shadow-sm">
                                Buka Kasus Paralegal
                            </Button>
                        </router-link>
                    </div>

                    <!-- Tier 3: Advokat -->
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-8 flex flex-col justify-between hover:shadow-md hover:border-slate-300 transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-6">
                                <Scale class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Advokat Profesional</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Pendampingan litigasi di persidangan atau negosiasi sengketa strategis oleh Advokat berizin PERADI dengan spesialisasi kasus terverifikasi.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Pendampingan sidang & kepolisian</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Konsultasi tatap muka / video call</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Surat kuasa khusus & eksekusi putusan</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client">
                            <Button variant="outline" class="w-full rounded-xl border-slate-200 font-medium hover:bg-white text-slate-700">
                                Konsultasi Advokat
                            </Button>
                        </router-link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 text-white py-12 px-4 sm:px-8 border-t border-slate-800">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#fc5000] text-white flex items-center justify-center font-bold text-sm">
                        R
                    </div>
                    <span class="font-bold text-base tracking-tight">RCI — Roys Counsel Indonesia</span>
                </div>
                <p class="text-xs text-slate-400 text-center sm:text-right">
                    © 2026 Roys Counsel Indonesia. Ditenagai oleh Vue 3, Vite & Shadcn UI.
                </p>
            </div>
        </footer>
    </div>
</template>
