<script setup>
import { AvatarFallback } from 'radix-vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    class: {
        type: [String, Object, Array],
        default: '',
    },
});
</script>

<template>
    <AvatarFallback
        :class="
            cn(
                'flex h-full w-full items-center justify-center rounded-full bg-[#fc5000] font-display text-sm font-medium text-white',
                props.class
            )
        "
    >
        <slot />
    </AvatarFallback>
</template>
