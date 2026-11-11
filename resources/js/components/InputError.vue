<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';
import { FieldError } from '@/components/ui/field';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <FieldError v-if="message" class="flex items-start gap-1.5">
        <CircleAlert class="mt-0.5 size-3.5 shrink-0" />
        <span>{{ message }}</span>
    </FieldError>
</template>
