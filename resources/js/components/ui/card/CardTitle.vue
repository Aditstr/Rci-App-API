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
    <h3
        :class="
            cn(
                'font-display text-xl font-semibold leading-none tracking-tight text-[#070607]',
                props.class
            )
        "
    >
        <slot />
    </h3>
</template>
