<script setup>
import { nextTick, reactive, ref } from 'vue';
import { CheckCircle2, Loader2, MessageSquare, Send } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import api from '@/services/api';

const categories = [
    { value: 'kritik', label: 'Kritik', description: 'Hal yang perlu diperbaiki' },
    { value: 'saran', label: 'Saran', description: 'Ide untuk pengembangan' },
    { value: 'kendala_teknis', label: 'Kendala teknis', description: 'Fitur yang bermasalah' },
];
const form = reactive({ category: 'saran', message: '', email: '' });
const submitting = ref(false);
const submitted = ref(false);
const errors = ref({});
const errorMessage = ref('');
const successHeading = ref(null);
const messageInput = ref(null);

async function submitFeedback() {
    if (submitting.value) return;
    errors.value = {};
    errorMessage.value = '';
    const message = form.message.trim();
    if (message.length < 10) {
        errors.value.message = ['Isi masukan setidaknya 10 karakter.'];
        return;
    }

    submitting.value = true;
    try {
        const response = await api.post('/feedback', {
            category: form.category,
            message,
            email: form.email.trim() || null,
        });
        if (response.status !== 201 || response.data.success !== true) {
            errorMessage.value = 'Masukan belum tersimpan. Silakan coba kembali.';
            return;
        }
        submitted.value = true;
        await nextTick();
        successHeading.value?.focus();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            errorMessage.value = 'Periksa kembali isian yang ditandai di bawah.';
        } else if (error.response?.status === 429) {
            errorMessage.value = error.response.data.message || 'Pengiriman terlalu sering. Silakan coba lagi nanti.';
        } else {
            errorMessage.value = 'Masukan belum terkirim. Periksa koneksi Anda, lalu coba kembali.';
        }
    } finally {
        submitting.value = false;
    }
}

async function startAgain() {
    Object.assign(form, { category: 'saran', message: '', email: '' });
    submitted.value = false;
    errors.value = {};
    errorMessage.value = '';
    await nextTick();
    messageInput.value?.$el?.focus();
}
</script>

<template>
    <div class="bg-[#fafafa] px-5 sm:px-8 py-10 sm:py-16">
        <div class="max-w-5xl mx-auto">
            <router-link to="/" class="text-sm text-slate-600 hover:text-[#fc5000] underline underline-offset-4">Kembali ke beranda</router-link>
            <div class="mt-7 mb-9 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#c94000] mb-3">Kami mendengarkan</p>
                <h1 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-950">Kritik &amp; Saran</h1>
                <p class="mt-4 text-slate-600 leading-relaxed">Ceritakan pengalaman Anda menggunakan RCI. Masukan Anda membantu kami memperbaiki layanan dan mengembangkan fitur yang Anda butuhkan.</p>
            </div>

            <div class="grid lg:grid-cols-[1fr_2fr] gap-6 lg:gap-10 items-start">
                <aside class="rounded-3xl border border-orange-100 bg-orange-50/60 p-6">
                    <div class="h-10 w-10 flex items-center justify-center rounded-2xl bg-[#fc5000] text-white mb-4">
                        <MessageSquare class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <h2 class="font-semibold text-slate-900">Apa yang ingin Anda sampaikan?</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Bagikan ide, pengalaman layanan, atau kendala saat menggunakan website. Untuk kendala teknis, jelaskan halaman dan langkah saat masalah muncul.</p>
                    <div class="mt-5 pt-5 border-t border-orange-100 text-sm leading-relaxed text-slate-600 space-y-3">
                        <p>Anda dapat mengirim masukan tanpa login.</p>
                        <p>Email bersifat opsional dan digunakan jika tim perlu menghubungi Anda terkait masukan ini.</p>
                    </div>
                </aside>

                <section v-if="submitted" class="rounded-3xl bg-white border border-slate-200 p-7 sm:p-10 shadow-sm" aria-live="polite">
                    <CheckCircle2 class="h-12 w-12 text-emerald-600 mb-5" aria-hidden="true" />
                    <h2 ref="successHeading" tabindex="-1" class="text-2xl font-bold text-slate-900 focus:outline-none">Masukan Anda sudah diterima</h2>
                    <p class="mt-3 text-slate-600 leading-relaxed">Terima kasih telah membantu RCI menjadi lebih baik. Tim kami akan meninjau masukan Anda.</p>
                    <Button type="button" variant="outline" class="mt-7 rounded-full" @click="startAgain">Kirim masukan lain</Button>
                </section>

                <form v-else class="rounded-3xl bg-white border border-slate-200 p-6 sm:p-8 shadow-sm space-y-7" :aria-busy="submitting" @submit.prevent="submitFeedback">
                    <p v-if="errorMessage" role="alert" class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">{{ errorMessage }}</p>

                    <fieldset :disabled="submitting">
                        <legend class="text-sm font-semibold text-slate-900 mb-3">Kategori masukan</legend>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <label v-for="category in categories" :key="category.value" class="relative cursor-pointer">
                                <input v-model="form.category" type="radio" name="category" :value="category.value" class="peer sr-only" required :aria-invalid="Boolean(errors.category)" :aria-describedby="errors.category ? 'category-error' : undefined" />
                                <span class="flex h-full flex-col gap-1 rounded-2xl border border-slate-200 p-4 transition-colors peer-checked:border-[#fc5000] peer-checked:bg-orange-50 peer-focus-visible:ring-2 peer-focus-visible:ring-[#fc5000] peer-focus-visible:ring-offset-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ category.label }}</span>
                                    <span class="text-xs text-slate-500 leading-relaxed">{{ category.description }}</span>
                                </span>
                            </label>
                        </div>
                        <p v-if="errors.category" id="category-error" role="alert" class="text-sm text-red-600 mt-2">{{ errors.category[0] }}</p>
                    </fieldset>

                    <div>
                        <label for="feedback-message" class="block text-sm font-semibold text-slate-900 mb-2">Isi kritik atau saran <span class="font-normal text-slate-500">(wajib)</span></label>
                        <Textarea ref="messageInput" id="feedback-message" v-model="form.message" rows="7" minlength="10" maxlength="5000" required :disabled="submitting" :aria-invalid="Boolean(errors.message)" :aria-describedby="errors.message ? 'message-help message-error' : 'message-help'" placeholder="Tuliskan pengalaman, ide, atau kendala yang ingin Anda sampaikan…" class="bg-white min-h-44 rounded-2xl border-slate-300 text-slate-900 focus-visible:ring-[#fc5000]/20 focus-visible:border-[#fc5000]" />
                        <div id="message-help" class="mt-2 flex justify-between gap-4 text-xs text-slate-500">
                            <span>Minimal 10 karakter.</span>
                            <span>{{ form.message.length.toLocaleString('id-ID') }} / 5.000</span>
                        </div>
                        <p v-if="errors.message" id="message-error" role="alert" class="text-sm text-red-600 mt-2">{{ errors.message[0] }}</p>
                    </div>

                    <div>
                        <label for="feedback-email" class="block text-sm font-semibold text-slate-900 mb-2">Email <span class="font-normal text-slate-500">(opsional)</span></label>
                        <Input id="feedback-email" v-model="form.email" type="email" autocomplete="email" maxlength="255" :disabled="submitting" :aria-invalid="Boolean(errors.email)" :aria-describedby="errors.email ? 'email-help email-error' : 'email-help'" placeholder="nama@email.com" class="bg-white border-slate-300 rounded-2xl" />
                        <p id="email-help" class="text-xs text-slate-500 mt-2">Isi jika Anda bersedia dihubungi untuk tindak lanjut.</p>
                        <p v-if="errors.email" id="email-error" role="alert" class="text-sm text-red-600 mt-2">{{ errors.email[0] }}</p>
                    </div>

                    <Button type="submit" :disabled="submitting" class="w-full sm:w-auto rounded-full gap-2 px-7 bg-[#fc5000] hover:bg-[#e04700] text-white">
                        <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" aria-hidden="true" />
                        <Send v-else class="h-4 w-4" aria-hidden="true" />
                        {{ submitting ? 'Mengirim…' : 'Kirim masukan' }}
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
