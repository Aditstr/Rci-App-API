<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Loader2, CheckCircle2 } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();
const done = ref(false);

const dashboardFor = (role) => (role === 'paralegal' ? '/paralegal' : role === 'lawyer' ? '/lawyer' : '/client');

onMounted(async () => {
    const params = new URLSearchParams(window.location.hash.slice(1));
    const token = params.get('token');
    const status = params.get('status');

    // Hapus token dari address bar / history
    window.history.replaceState(window.history.state, '', window.location.pathname);

    if (token) {
        auth.setToken(token);
    }

    // Tidak ada sesi di browser ini (mis. link "already" dibuka di perangkat lain)
    if (!auth.token) {
        router.replace({ name: 'login', query: { verified: status === 'already' ? 'already' : '1' } });
        return;
    }

    // Segarkan data user agar is_verified di localStorage tidak basi
    await auth.fetchUser();

    if (!auth.token || !auth.user) {
        router.replace({ name: 'login', query: { verified: '1' } });
        return;
    }

    if (auth.user.is_verified === false) {
        router.replace({ name: 'check-email', query: { email: auth.user.email } });
        return;
    }

    done.value = true;
    setTimeout(() => router.replace(dashboardFor(auth.user.role)), 900);
});
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] flex flex-col items-center justify-center p-4 sm:p-8">
        <router-link to="/" class="flex items-center gap-3 mb-8 no-underline">
            <div class="w-12 h-12 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display text-2xl font-bold shadow-md">R</div>
            <span class="font-display text-3xl font-bold tracking-tight text-[#070607]">RCI</span>
        </router-link>

        <Card class="w-full max-w-md bg-[#f7f6f2] p-2 sm:p-4 rounded-[36px] shadow-xl border-black/10">
            <CardHeader class="space-y-2 text-center">
                <CheckCircle2 v-if="done" class="w-12 h-12 mx-auto text-[#fc5000]" />
                <Loader2 v-else class="w-12 h-12 mx-auto text-[#fc5000] animate-spin" />
                <CardTitle class="font-display text-2xl font-bold tracking-tight text-[#070607]">
                    {{ done ? 'EMAIL TERVERIFIKASI' : 'MEMVERIFIKASI...' }}
                </CardTitle>
                <CardDescription>
                    {{ done ? 'Mengalihkan ke dashboard Anda...' : 'Mohon tunggu sebentar.' }}
                </CardDescription>
            </CardHeader>
        </Card>
    </div>
</template>
