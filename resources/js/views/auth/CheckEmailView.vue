<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { MailCheck, Loader2, Info } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref(route.query.email || JSON.parse(localStorage.getItem('rci_user') || 'null')?.email || '');
const notice = ref('');
const error = ref('');

const handleResend = async () => {
    notice.value = '';
    error.value = '';
    if (!email.value) {
        error.value = 'Isi email dulu untuk kirim ulang.';
        return;
    }
    const result = await auth.resend(email.value);
    if (result.success) {
        notice.value = 'Jika email terdaftar dan belum terverifikasi, link verifikasi telah dikirim. Cek inbox & spam.';
    } else {
        error.value = result.message;
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] flex flex-col items-center justify-center p-4 sm:p-8">
        <router-link to="/" class="flex items-center gap-3 mb-8 no-underline">
            <div class="w-12 h-12 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display text-2xl font-bold shadow-md">R</div>
            <span class="font-display text-3xl font-bold tracking-tight text-[#070607]">RCI</span>
        </router-link>

        <Card class="w-full max-w-md bg-[#f7f6f2] p-2 sm:p-4 rounded-[36px] shadow-xl border-black/10">
            <CardHeader class="space-y-1 text-center">
                <MailCheck class="w-10 h-10 mx-auto text-[#fc5000]" />
                <CardTitle class="font-display text-2xl font-bold tracking-tight text-[#070607]">CEK EMAIL KAMU</CardTitle>
                <CardDescription>
                    Pendaftaran berhasil<span v-if="email"> untuk <strong>{{ email }}</strong></span>.
                    Klik link verifikasi di inbox (cek spam juga). Link berlaku 60 menit.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <Alert v-if="notice" class="border-green-200 bg-green-50">
                    <Info class="w-4 h-4" />
                    <AlertTitle>Terkirim</AlertTitle>
                    <AlertDescription>{{ notice }}</AlertDescription>
                </Alert>
                <Alert v-if="error" variant="destructive">
                    <AlertTitle>Gagal</AlertTitle>
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>
                <Button @click="handleResend" class="w-full h-12 rounded-full" :disabled="auth.loading">
                    <Loader2 v-if="auth.loading" class="w-4 h-4 mr-2 animate-spin" />
                    {{ auth.loading ? 'Mengirim...' : 'Kirim Ulang Link' }}
                </Button>
                <Button variant="outline" class="w-full h-12 rounded-full" @click="router.push('/login')">Ke Halaman Masuk</Button>
            </CardContent>
        </Card>
    </div>
</template>
