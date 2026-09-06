<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { 
    Sparkles, 
    Scale, 
    Users, 
    ShieldCheck, 
    ArrowRight, 
    CheckCircle2, 
    Search,
    Bot,
    Send,
    FileText,
    ChevronRight,
    Zap,
    Lock
} from 'lucide-vue-next';

const router = useRouter();
const quickPrompt = ref('');

const startAiChat = (customText = null) => {
    const query = customText || quickPrompt.value;
    if (query && query.trim()) {
        router.push({ path: '/client/ai-chat', query: { q: query.trim() } });
    } else {
        router.push('/client/ai-chat');
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] text-slate-900 font-sans">
        
        <!-- Hero Section -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-28 overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-b from-orange-100/50 to-transparent blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center flex flex-col items-center">
                
                <!-- Announcement Pill Badge -->
                <router-link 
                    to="/client/ai-chat" 
                    class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 px-4 py-1.5 text-xs font-semibold text-orange-700 shadow-xs hover:border-orange-300 transition-all mb-8 group"
                >
                    <span class="flex h-2 w-2 rounded-full bg-[#fc5000] animate-pulse"></span>
                    <span>RCI Legal Copilot AI 2.0 Kini Telah Aktif</span>
                    <ChevronRight class="w-3.5 h-3.5 text-orange-400 group-hover:translate-x-0.5 transition-transform" />
                </router-link>

                <!-- Hero Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-slate-950 max-w-3xl leading-[1.15] mb-6">
                    Solusi Hukum Cerdas untuk <br class="hidden sm:inline" />
                    <span class="bg-gradient-to-r from-[#fc5000] via-orange-600 to-amber-500 bg-clip-text text-transparent">
                        Bisnis & Personal
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-xl text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed font-normal">
                    Platform legalitas terpadu di Indonesia. Dapatkan analisis regulasi instan dengan AI, atau ajukan penanganan perkara ke Paralegal dan Advokat bersertifikasi resmi PERADI.
                </p>

                <!-- Prompt Input Bar -->
                <div class="w-full max-w-2xl bg-white p-2 rounded-2xl border border-slate-200 shadow-md hover:shadow-lg transition-shadow mb-8 flex items-center gap-2">
                    <div class="pl-3 text-slate-400">
                        <Search class="w-5 h-5" />
                    </div>
                    <input
                        v-model="quickPrompt"
                        @keyup.enter="startAiChat()"
                        type="text"
                        placeholder="Ketik pertanyaan hukum Anda... (cth: wanprestasi kontrak bisnis)"
                        class="flex-1 bg-transparent px-3 py-2 text-sm sm:text-base text-slate-900 placeholder:text-slate-400 outline-none"
                    />
                    <Button
                        @click="startAiChat()"
                        class="bg-[#fc5000] hover:bg-[#e04700] text-white rounded-xl font-medium px-5 h-11 gap-2 shrink-0 shadow-xs text-sm"
                    >
                        <span>Tanya AI</span>
                        <ArrowRight class="w-4 h-4" />
                    </Button>
                </div>

                <!-- Trust badges -->
                <div class="flex flex-wrap items-center justify-center gap-6 text-xs sm:text-sm text-slate-500 mb-16">
                    <div class="flex items-center gap-2">
                        <Zap class="w-4 h-4 text-[#fc5000]" />
                        <span>3 Analisis Gratis / Hari</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Lock class="w-4 h-4 text-emerald-600" />
                        <span>Kerahasiaan Klien Terjamin</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-blue-600" />
                        <span>Advokat Terverifikasi PERADI</span>
                    </div>
                </div>

                <!-- Interactive Copilot Mockup Window -->
                <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200/90 shadow-2xl overflow-hidden text-left">
                    <!-- Window Header -->
                    <div class="bg-slate-50/80 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            <span class="ml-2 text-xs font-semibold text-slate-600">RCI Legal Copilot — Simulasi Kasus</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Siap Membantu
                        </span>
                    </div>

                    <!-- Chat conversation preview -->
                    <div class="p-6 space-y-4 bg-slate-50/30">
                        <!-- User question -->
                        <div class="flex gap-3 justify-end">
                            <div class="bg-[#0f172a] text-white text-xs sm:text-sm rounded-2xl rounded-tr-none px-4 py-3 max-w-[85%] leading-relaxed shadow-xs">
                                "Rekan bisnis saya terlambat membayar invoice proyek pengadaan senilai Rp 85 juta lebih dari 60 hari. Klausul kontrak menyebut jatuh tempo 30 hari. Apa langkah hukum tercepat?"
                            </div>
                        </div>

                        <!-- AI Response -->
                        <div class="flex gap-3 items-start">
                            <div class="w-8 h-8 rounded-xl bg-[#fc5000] text-white flex items-center justify-center shrink-0 shadow-sm mt-1">
                                <Bot class="w-4 h-4" />
                            </div>
                            <div class="bg-white border border-slate-200 text-slate-800 text-xs sm:text-sm rounded-2xl rounded-tl-none p-5 max-w-[90%] shadow-sm space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                    <span class="font-bold text-xs text-[#fc5000] uppercase tracking-wider">Analisis Regulasi (Pasal 1243 KUHPerdata)</span>
                                    <span class="text-[11px] text-slate-400">Kategori: Wanprestasi Kontrak</span>
                                </div>
                                <p class="text-slate-600 leading-relaxed text-xs sm:text-sm">
                                    Berdasarkan ketentuan hukum perdata, keterlambatan pembayaran melampaui tenggat perjanjian merupakan bentuk <strong>wanprestasi</strong>. Berikut tahapan yang disarankan:
                                </p>
                                <ol class="space-y-1.5 text-xs sm:text-sm text-slate-700 list-decimal pl-4">
                                    <li><strong>Terbitkan Surat Teguran (Somasi I):</strong> Berikan batas waktu pelunasan 7 hari kalender.</li>
                                    <li><strong>Klaim Ganti Rugi & Bunga:</strong> Anda berhak menuntut biaya kerugian dan bunga moratoir jika tercantum di SPK.</li>
                                    <li><strong>Opsi Eskalasi:</strong> Jika somasi diabaikan, Paralegal atau Advokat RCI dapat memfasilitasi mediasi atau mengajukan gugatan perdata.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Mockup Action -->
                    <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
                        <span class="text-xs text-slate-500">Ingin menguji kasus dokumen Anda sendiri?</span>
                        <Button 
                            @click="startAiChat('Contoh draf somasi wanprestasi tagihan')" 
                            size="sm"
                            class="bg-[#fc5000] hover:bg-[#e04700] text-white rounded-lg text-xs font-medium px-4 h-9 gap-1.5"
                        >
                            <span>Coba Chat AI Sekarang</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>

            </div>
        </section>

        <!-- Services Section -->
        <section id="layanan" class="bg-white py-20 sm:py-28 border-t border-slate-200/80">
            <div class="max-w-6xl mx-auto px-6 sm:px-8 lg:px-12">
                
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200/80 mb-4">
                        Pilihan Layanan RCI
                    </span>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-950 mb-4">
                        Ekosistem Hukum Fleksibel Sesuai Kebutuhan
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        Pilih tingkat penanganan yang paling efisien, mulai dari riset cepat AI hingga pendampingan hukum di pengadilan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 items-stretch">
                    
                    <!-- Tier 1: AI -->
                    <div class="bg-slate-50/70 rounded-2xl border border-slate-200 p-8 flex flex-col justify-between hover:shadow-lg hover:border-slate-300 transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-orange-100 text-[#fc5000] flex items-center justify-center mb-6">
                                <Sparkles class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Konsultasi AI Cerdas</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Dapatkan analisis hukum awal instan. Sangat tepat untuk riset pasal, penelaahan klausul kontrak sederhana, dan tanya jawab hukum harian.
                            </p>
                            <ul class="space-y-3 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Tersedia 24/7 tanpa antre</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Rujukan KUHPerdata & UU Cipta Kerja</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Gratis 3 pertanyaan setiap hari</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client/ai-chat">
                            <Button variant="outline" class="w-full rounded-xl border-slate-200 font-medium hover:bg-white text-slate-800">
                                Buka Chat AI
                            </Button>
                        </router-link>
                    </div>

                    <!-- Tier 2: Paralegal (Featured) -->
                    <div class="bg-white rounded-2xl border-2 border-[#fc5000] p-8 flex flex-col justify-between shadow-xl relative hover:-translate-y-1 transition-all">
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="bg-[#fc5000] text-white uppercase text-[10px] font-bold tracking-wider px-3.5 py-1 rounded-full shadow-sm">
                                Paling Populer
                            </span>
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-[#fc5000] text-white flex items-center justify-center mb-6 shadow-sm">
                                <Users class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Bantuan Paralegal</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Penelaahan berkas legalitas, penyusunan draf somasi resmi, dan audit kontrak dibantu langsung oleh praktisi hukum terlatih.
                            </p>
                            <ul class="space-y-3 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Review draf kontrak & perjanjian</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Penyusunan surat somasi resmi</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-[#fc5000] shrink-0" />
                                    <span>Sistem Rekber Escrow terlindungi</span>
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
                    <div class="bg-slate-50/70 rounded-2xl border border-slate-200 p-8 flex flex-col justify-between hover:shadow-lg hover:border-slate-300 transition-all">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-6">
                                <Scale class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Advokat Profesional</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                                Pendampingan litigasi sidang pengadilan atau penanganan sengketa komersial bernilai tinggi oleh Advokat berlisensi resmi.
                            </p>
                            <ul class="space-y-3 text-xs sm:text-sm text-slate-600 mb-8">
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Pendampingan sidang & kepolisian</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Konsultasi privat via video conference</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>Surat kuasa khusus & eksekusi putusan</span>
                                </li>
                            </ul>
                        </div>
                        <router-link to="/client">
                            <Button variant="outline" class="w-full rounded-xl border-slate-200 font-medium hover:bg-white text-slate-800">
                                Konsultasi Advokat
                            </Button>
                        </router-link>
                    </div>

                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-950 text-white py-12 px-6 sm:px-8 lg:px-12 border-t border-slate-900">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-[#fc5000] text-white flex items-center justify-center font-bold text-sm">
                        R
                    </div>
                    <span class="font-bold text-base tracking-tight text-white">RCI — Roys Counsel Indonesia</span>
                </div>
                <p class="text-xs text-slate-400 text-center sm:text-right">
                    © 2026 Roys Counsel Indonesia. All rights reserved.
                </p>
            </div>
        </footer>

    </div>
</template>
