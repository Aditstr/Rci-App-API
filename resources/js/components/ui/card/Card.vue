<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    class: {
        type: [String, Object, Array],
        default: '',
    },
});
</script>

<template>
    <div
        :class="
            cn(
                'rounded-3xl border border-black/10 bg-[#f7f6f2] text-[#070607] shadow-sm transition-all',
                props.class
            )
        "
    >
        <slot />
    </div>
</template>
