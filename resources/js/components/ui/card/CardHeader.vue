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
    <div :class="cn('flex flex-col space-y-1.5 p-6 pb-3', props.class)">
        <slot />
    </div>
</template>
