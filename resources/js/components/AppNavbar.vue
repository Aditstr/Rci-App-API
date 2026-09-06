<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Menu, X, LogOut, LayoutDashboard, MessageSquare, Sparkles } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();
const mobileOpen = ref(false);

const handleLogout = async () => {
    await auth.logout();
    router.push('/login');
};
</script>

<template>
    <nav class="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/85 backdrop-blur-md transition-all">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-8 py-3.5">
            <!-- Logo -->
            <router-link to="/" class="flex items-center gap-2.5 text-inherit no-underline group">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#fc5000] to-orange-600 text-white flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition-transform">
                    R
                </div>
                <span class="font-extrabold text-xl tracking-tight text-slate-900">
                    RCI<span class="text-[#fc5000]">.</span>
                </span>
            </router-link>

            <!-- Desktop Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <router-link to="/" class="hover:text-slate-900 transition-colors">
                    Beranda
                </router-link>
                <router-link to="/client/ai-chat" class="hover:text-[#fc5000] transition-colors flex items-center gap-1.5 font-semibold text-slate-800">
                    <Sparkles class="w-4 h-4 text-[#fc5000]" />
                    Konsultasi AI
                </router-link>
                <a href="/#layanan" class="hover:text-slate-900 transition-colors">
                    Layanan
                </a>
                <a href="/#cara-kerja" class="hover:text-slate-900 transition-colors">
                    Cara Kerja
                </a>
            </div>

            <!-- Actions -->
            <div class="hidden md:flex items-center gap-3">
                <template v-if="auth.isAuthenticated">
                    <router-link to="/client">
                        <Button variant="outline" size="sm" class="gap-2 rounded-full border-slate-200 hover:bg-slate-50 text-slate-700">
                            <LayoutDashboard class="w-4 h-4" />
                            Dashboard
                        </Button>
                    </router-link>
                    <div class="flex items-center gap-2.5 pl-2 border-l border-slate-200">
                        <Avatar class="h-8 w-8 ring-2 ring-[#fc5000]/20">
                            <AvatarFallback class="bg-[#fc5000] text-white font-semibold text-xs">
                                {{ auth.userName.charAt(0).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="text-sm font-medium max-w-[130px] truncate text-slate-800">
                            {{ auth.userName }}
                        </span>
                    </div>
                    <Button variant="ghost" size="icon" @click="handleLogout" title="Keluar" class="text-slate-400 hover:text-red-600">
                        <LogOut class="w-4 h-4" />
                    </Button>
                </template>
                <template v-else>
                    <router-link to="/login">
                        <Button variant="ghost" size="sm" class="font-medium text-slate-700 hover:text-slate-900">
                            Masuk
                        </Button>
                    </router-link>
                    <router-link to="/register">
                        <Button size="sm" class="bg-[#fc5000] hover:bg-[#e04700] text-white rounded-full font-medium px-5 shadow-sm">
                            Mulai Gratis
                        </Button>
                    </router-link>
                </template>
            </div>

            <!-- Mobile toggle -->
            <button
                type="button"
                class="md:hidden p-2 text-slate-700 hover:text-slate-900"
                @click="mobileOpen = !mobileOpen"
                aria-label="Toggle navigation"
            >
                <Menu v-if="!mobileOpen" class="w-6 h-6" />
                <X v-else class="w-6 h-6" />
            </button>
        </div>

        <!-- Mobile Drawer -->
        <div
            v-if="mobileOpen"
            class="md:hidden border-t border-slate-200 bg-white px-6 py-4 flex flex-col gap-3 shadow-lg"
        >
            <router-link
                to="/"
                class="py-2 text-sm font-medium text-slate-700 hover:text-[#fc5000]"
                @click="mobileOpen = false"
            >
                Beranda
            </router-link>
            <router-link
                to="/client/ai-chat"
                class="py-2 text-sm font-medium flex items-center gap-2 text-slate-700 hover:text-[#fc5000]"
                @click="mobileOpen = false"
            >
                <Sparkles class="w-4 h-4 text-[#fc5000]" />
                Konsultasi AI
            </router-link>
            <a
                href="/#layanan"
                class="py-2 text-sm font-medium text-slate-700 hover:text-[#fc5000]"
                @click="mobileOpen = false"
            >
                Layanan
            </a>
            <hr class="border-slate-100 my-1" />
            <template v-if="auth.isAuthenticated">
                <router-link
                    to="/client"
                    class="py-2 text-sm font-medium flex items-center gap-2 text-slate-800"
                    @click="mobileOpen = false"
                >
                    <LayoutDashboard class="w-4 h-4 text-[#fc5000]" />
                    Dashboard ({{ auth.userName }})
                </router-link>
                <Button variant="destructive" size="sm" @click="handleLogout" class="w-full mt-2">
                    <LogOut class="w-4 h-4 mr-2" />
                    Keluar
                </Button>
            </template>
            <template v-else>
                <router-link to="/login" @click="mobileOpen = false" class="w-full">
                    <Button variant="outline" class="w-full rounded-full">Masuk</Button>
                </router-link>
                <router-link to="/register" @click="mobileOpen = false" class="w-full">
                    <Button class="w-full bg-[#fc5000] hover:bg-[#e04700] text-white rounded-full">Mulai Gratis</Button>
                </router-link>
            </template>
        </div>
    </nav>
</template>
