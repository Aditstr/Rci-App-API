<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, Loader2, UserCheck, Scale, Briefcase } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();

const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const role = ref('client');
const errorMessage = ref('');

const handleRegister = async () => {
    errorMessage.value = '';
    if (password.value !== password_confirmation.value) {
        errorMessage.value = 'Konfirmasi kata sandi tidak cocok.';
        return;
    }

    const result = await auth.register({
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value,
        role: role.value,
    });

    if (result.success) {
        if (role.value === 'paralegal') {
            router.push('/paralegal');
        } else if (role.value === 'lawyer') {
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
        <router-link to="/" class="flex items-center gap-3 mb-8 no-underline">
            <div class="w-12 h-12 rounded-full bg-[#fc5000] text-white flex items-center justify-center font-display text-2xl font-bold shadow-md">
                R
            </div>
            <span class="font-display text-3xl font-bold tracking-tight text-[#070607]">
                RCI
            </span>
        </router-link>

        <Card class="w-full max-w-lg bg-[#f7f6f2] p-2 sm:p-4 rounded-[36px] shadow-xl border-black/10">
            <CardHeader class="space-y-1">
                <CardTitle class="font-display text-3xl font-bold tracking-tight text-[#070607]">
                    DAFTAR AKUN
                </CardTitle>
                <CardDescription>
                    Bergabung bersama Roys Counsel Indonesia
                </CardDescription>
            </CardHeader>

            <CardContent>
                <Alert v-if="errorMessage" variant="destructive" class="mb-6">
                    <AlertCircle class="w-4 h-4" />
                    <AlertTitle>Registrasi Gagal</AlertTitle>
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <form @submit.prevent="handleRegister" class="space-y-4">
                    <!-- Role Picker -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                            Pilih Peran Akun
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="role = 'client'"
                                :class="[
                                    'p-3 rounded-2xl border text-left flex flex-col gap-1 transition-all cursor-pointer',
                                    role === 'client' 
                                        ? 'border-[#fc5000] bg-[#fc5000]/10 text-[#fc5000] font-bold' 
                                        : 'border-black/10 bg-white/50 text-black/70 hover:bg-white'
                                ]"
                            >
                                <UserCheck class="w-5 h-5" />
                                <span class="text-xs">Klien</span>
                            </button>

                            <button
                                type="button"
                                @click="role = 'paralegal'"
                                :class="[
                                    'p-3 rounded-2xl border text-left flex flex-col gap-1 transition-all cursor-pointer',
                                    role === 'paralegal' 
                                        ? 'border-[#fc5000] bg-[#fc5000]/10 text-[#fc5000] font-bold' 
                                        : 'border-black/10 bg-white/50 text-black/70 hover:bg-white'
                                ]"
                            >
                                <Briefcase class="w-5 h-5" />
                                <span class="text-xs">Paralegal</span>
                            </button>

                            <button
                                type="button"
                                @click="role = 'lawyer'"
                                :class="[
                                    'p-3 rounded-2xl border text-left flex flex-col gap-1 transition-all cursor-pointer',
                                    role === 'lawyer' 
                                        ? 'border-[#fc5000] bg-[#fc5000]/10 text-[#fc5000] font-bold' 
                                        : 'border-black/10 bg-white/50 text-black/70 hover:bg-white'
                                ]"
                            >
                                <Scale class="w-5 h-5" />
                                <span class="text-xs">Advokat</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                            Nama Lengkap
                        </label>
                        <Input
                            v-model="name"
                            type="text"
                            placeholder="Nama sesuai KTP"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                            Email
                        </label>
                        <Input
                            v-model="email"
                            type="email"
                            placeholder="nama@email.com"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                                Kata Sandi
                            </label>
                            <Input
                                v-model="password"
                                type="password"
                                placeholder="Min. 8 karakter"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-black/70">
                                Konfirmasi Sandi
                            </label>
                            <Input
                                v-model="password_confirmation"
                                type="password"
                                placeholder="Ulangi sandi"
                                required
                            />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold mt-4 rounded-full"
                        :disabled="auth.loading"
                    >
                        <Loader2 v-if="auth.loading" class="w-4 h-4 mr-2 animate-spin" />
                        {{ auth.loading ? 'Mendaftarkan...' : 'Buat Akun Sekarang' }}
                    </Button>
                </form>
            </CardContent>

            <CardFooter class="justify-center border-t border-black/5 pt-4 text-xs text-black/60">
                Sudah punya akun?
                <router-link to="/login" class="ml-1 text-[#fc5000] font-semibold hover:underline">
                    Masuk di sini
                </router-link>
            </CardFooter>
        </Card>
    </div>
</template>
