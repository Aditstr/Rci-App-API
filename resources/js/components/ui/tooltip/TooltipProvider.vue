<script setup>
import { TooltipProvider } from "reka-ui";

const props = defineProps();
</script>

<template>
  <TooltipProvider v-bind="props">
    <slot />
  </TooltipProvider>
</template>
