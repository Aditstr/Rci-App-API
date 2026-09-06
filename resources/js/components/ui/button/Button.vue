<script setup>
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from './index';

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
        :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
