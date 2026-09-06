<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Menu, X, LogOut, LayoutDashboard, MessageSquare } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();
const mobileOpen = ref(false);

const handleLogout = async () => {
    await auth.logout();
    router.push('/login');
};
</script>

<template>
    <nav class="sticky top-0 z-50 w-full bg-[#e2e2df]/80 backdrop-blur-md px-4 sm:px-8 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4 bg-[#f7f6f2] rounded-full px-6 py-3 border border-black/10 shadow-sm">
            <!-- Logo -->
            <router-link to="/" class="flex items-center gap-3 text-inherit no-underline">
                <div class="w-9 h-9 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display text-lg font-bold">
                    R
                </div>
                <span class="font-display text-2xl font-bold tracking-tight text-[#070607]">
                    RCI
                </span>
            </router-link>

            <!-- Desktop Links -->
            <div class="hidden md:flex items-center gap-6 text-sm font-medium text-[#070607]/80">
                <router-link to="/" class="hover:text-[#fc5000] transition-colors">
                    Beranda
                </router-link>
                <span class="text-black/20">|</span>
                <router-link to="/client/ai-chat" class="hover:text-[#fc5000] transition-colors flex items-center gap-1.5">
                    <MessageSquare class="w-4 h-4 text-[#fc5000]" />
                    Konsultasi AI
                </router-link>
                <span class="text-black/20">|</span>
                <a href="/#layanan" class="hover:text-[#fc5000] transition-colors">
                    Layanan
                </a>
            </div>

            <!-- Actions -->
            <div class="hidden md:flex items-center gap-3">
                <template v-if="auth.isAuthenticated">
                    <router-link to="/client">
                        <Button variant="outline" size="sm" class="gap-2">
                            <LayoutDashboard class="w-4 h-4" />
                            Dashboard
                        </Button>
                    </router-link>
                    <div class="flex items-center gap-2 pl-2">
                        <Avatar class="h-8 w-8">
                            <AvatarFallback>{{ auth.userName.charAt(0).toUpperCase() }}</AvatarFallback>
                        </Avatar>
                        <span class="text-sm font-semibold max-w-[120px] truncate text-[#070607]">
                            {{ auth.userName }}
                        </span>
                    </div>
                    <Button variant="ghost" size="icon" @click="handleLogout" title="Keluar">
                        <LogOut class="w-4 h-4 text-red-500" />
                    </Button>
                </template>
                <template v-else>
                    <router-link to="/login">
                        <Button variant="ghost" size="sm">
                            Masuk
                        </Button>
                    </router-link>
                    <router-link to="/register">
                        <Button variant="default" size="sm">
                            Mulai Gratis
                        </Button>
                    </router-link>
                </template>
            </div>

            <!-- Mobile toggle -->
            <button
                type="button"
                class="md:hidden p-2 text-[#070607]"
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
            class="md:hidden mt-2 p-4 bg-[#f7f6f2] rounded-3xl border border-black/10 flex flex-col gap-3 shadow-lg"
        >
            <router-link
                to="/"
                class="p-2 text-sm font-medium hover:text-[#fc5000]"
                @click="mobileOpen = false"
            >
                Beranda
            </router-link>
            <router-link
                to="/client/ai-chat"
                class="p-2 text-sm font-medium hover:text-[#fc5000]"
                @click="mobileOpen = false"
            >
                Konsultasi AI
            </router-link>
            <hr class="border-black/10 my-1" />
            <template v-if="auth.isAuthenticated">
                <router-link
                    to="/client"
                    class="p-2 text-sm font-medium flex items-center gap-2"
                    @click="mobileOpen = false"
                >
                    <LayoutDashboard class="w-4 h-4" />
                    Dashboard ({{ auth.userName }})
                </router-link>
                <Button variant="destructive" size="sm" @click="handleLogout">
                    <LogOut class="w-4 h-4 mr-2" />
                    Keluar
                </Button>
            </template>
            <template v-else>
                <router-link to="/login" @click="mobileOpen = false">
                    <Button variant="outline" class="w-full">Masuk</Button>
                </router-link>
                <router-link to="/register" @click="mobileOpen = false">
                    <Button variant="default" class="w-full">Mulai Gratis</Button>
                </router-link>
            </template>
        </div>
    </nav>
</template>
