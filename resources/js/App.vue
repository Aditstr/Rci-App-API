<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppNavbar from '@/components/AppNavbar.vue';

const route = useRoute();

// Show navbar on all routes except login/register if desired, or show everywhere
const showNavbar = computed(() => {
    return !['login', 'register'].includes(route.name);
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white text-slate-900">
        <AppNavbar v-if="showNavbar" />
        <main class="flex-1">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
