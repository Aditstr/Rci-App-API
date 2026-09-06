<script setup>
import { Primitive } from 'radix-vue';
import { buttonVariants } from './index';
import { cn } from '@/lib/utils';

const props = defineProps({
    as: {
        type: String,
        default: 'button',
    },
    asChild: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'default',
    },
    size: {
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
    <Primitive
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
