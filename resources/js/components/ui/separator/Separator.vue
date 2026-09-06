<script setup>
import { Separator } from 'radix-vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    orientation: {
        type: String,
        default: 'horizontal',
    },
    decorative: {
        type: Boolean,
        default: true,
    },
    class: {
        type: [String, Object, Array],
        default: '',
    },
});
</script>

<template>
    <Separator
        :orientation="orientation"
        :decorative="decorative"
        :class="
            cn(
                'shrink-0 bg-black/10',
                orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
                props.class
            )
        "
    />
</template>
