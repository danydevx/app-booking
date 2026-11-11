<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <div
        v-if="message"
        role="alert"
        class="flex items-start gap-1.5 text-sm text-destructive"
    >
        <CircleAlert class="mt-0.5 size-3.5 shrink-0" />
        <p>{{ message }}</p>
    </div>
</template>
