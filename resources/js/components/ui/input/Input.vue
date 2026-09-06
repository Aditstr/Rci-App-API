<script setup>
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps({
    defaultValue: {
        type: [String, Number],
        default: undefined,
    },
    modelValue: {
        type: [String, Number],
        default: undefined,
    },
    class: {
        type: [String, Object, Array],
        default: '',
    },
});

const emits = defineEmits(['update:modelValue']);
</script>

<template>
    <input
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :class="
            cn(
                'flex h-12 w-full rounded-full border border-[#070607]/40 bg-[#f7f6f2] px-5 py-2 text-sm text-[#070607] shadow-sm transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-[#070607]/40 focus-visible:border-[#fc5000] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fc5000]/20 disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )
        "
    />
</template>
