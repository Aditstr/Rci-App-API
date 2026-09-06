<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Eye, EyeOff, AlertCircle, Loader2 } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
    errorMessage.value = '';
    const result = await auth.login({
        email: email.value,
        password: password.value,
    });

    if (result.success) {
        const role = result.role;
        if (role === 'paralegal') {
            router.push('/paralegal');
        } else if (role === 'lawyer') {
            router.push('/lawyer');
        } else {
            router.push('/client');
        }
    } else {
        errorMessage.value = result.message;
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#e2e2df] flex flex-col items-center justify-center p-4 sm:p-8">
        <!-- Logo Header -->
        <router-link to="/" class="flex items-center gap-3 mb-8 no-underline">
            <div class="w-12 h-12 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display text-2xl font-bold shadow-md">
                R
            </div>
            <span class="font-display text-3xl font-bold tracking-tight text-[#070607]">
                RCI
            </span>
        </router-link>

        <Card class="w-full max-w-md bg-[#f7f6f2] p-2 sm:p-4 rounded-[36px] shadow-xl border-black/10">
            <CardHeader class="space-y-1">
                <CardTitle class="font-display text-3xl font-bold tracking-tight text-[#070607]">
                    MASUK
                </CardTitle>
                <CardDescription>
                    Selamat datang kembali di Roys Counsel Indonesia
                </CardDescription>
            </CardHeader>

            <CardContent>
                <!-- Error Alert -->
                <Alert v-if="errorMessage" variant="destructive" class="mb-6">
                    <AlertCircle class="w-4 h-4" />
                    <AlertTitle>Gagal Masuk</AlertTitle>
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <form @submit.prevent="handleLogin" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                            Email
                        </label>
                        <Input
                            v-model="email"
                            type="email"
                            placeholder="nama@email.com"
                            required
                            autofocus
                        />
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                                Kata Sandi
                            </label>
                            <a href="#" class="text-xs text-[#fc5000] hover:underline">
                                Lupa kata sandi?
                            </a>
                        </div>
                        <div class="relative">
                            <Input
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                required
                                class="pr-12"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-black/40 hover:text-black transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold mt-4 rounded-full"
                        :disabled="auth.loading"
                    >
                        <Loader2 v-if="auth.loading" class="w-4 h-4 mr-2 animate-spin" />
                        {{ auth.loading ? 'Memproses...' : 'Masuk Sekarang' }}
                    </Button>
                </form>

                <div class="relative my-6 text-center text-xs after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-black/10">
                    <span class="relative z-10 bg-[#f7f6f2] px-3 text-black/50 uppercase font-medium">
                        Atau
                    </span>
                </div>

                <!-- Google OAuth -->
                <a href="/api/v1/auth/google" class="block w-full">
                    <Button variant="outline" class="w-full h-12 rounded-full gap-3 font-medium bg-white/60">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Lanjutkan dengan Google
                    </Button>
                </a>
            </CardContent>

            <CardFooter class="justify-center border-t border-black/5 pt-4 text-xs text-black/60">
                Belum memiliki akun?
                <router-link to="/register" class="ml-1 text-[#fc5000] font-semibold hover:underline">
                    Daftar gratis
                </router-link>
            </CardFooter>
        </Card>
    </div>
</template>
