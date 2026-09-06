<script setup>
import { alertVariants } from './index';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
    },
    class: {
        type: [String, Object, Array],
        default: '',
    },
});
</script>

<template>
    <div :class="cn(alertVariants({ variant }), props.class)" role="alert">
        <slot />
    </div>
</template>
