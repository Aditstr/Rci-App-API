<script setup>
import { MessageSquare } from 'lucide-vue-next';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="bg-slate-950 text-white py-10 px-6 sm:px-8 border-t border-slate-900">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
            <router-link to="/" class="flex items-center gap-2.5 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-400">
                <span class="w-7 h-7 rounded-lg bg-[#fc5000] flex items-center justify-center font-bold text-sm">R</span>
                <span class="font-bold text-sm sm:text-base">RCI — Roys Counsel Indonesia</span>
            </router-link>
            <div class="flex flex-col items-center sm:items-end gap-3">
                <router-link
                    to="/kritik-saran"
                    class="inline-flex items-center gap-2 text-sm text-slate-200 hover:text-orange-300 rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-400"
                >
                    <MessageSquare class="w-4 h-4" aria-hidden="true" />
                    Kritik &amp; Saran
                </router-link>
                <p class="text-xs text-slate-400 text-center">© {{ year }} Roys Counsel Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>
</template>
