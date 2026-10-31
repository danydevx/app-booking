<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    action: string;
    title: string;
    description: string;
    submitLabel: string;
    placeholder?: string;
    destructive?: boolean;
}>();

const form = useForm({ reason: '' });

function submit(): void {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => form.reset('reason'),
    });
}
</script>

<template>
    <div class="space-y-3 rounded-xl border p-4">
        <div>
            <p class="text-sm font-medium">{{ title }}</p>
            <p class="text-xs text-muted-foreground">{{ description }}</p>
        </div>

        <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="submit">
            <Input
                v-model="form.reason"
                :placeholder="placeholder ?? 'Motivo obligatorio (mín. 5 caracteres)'"
                aria-label="Motivo"
                class="flex-1"
            />
            <Button
                type="submit"
                :variant="destructive ? 'destructive' : 'default'"
                :disabled="form.processing"
            >
                {{ submitLabel }}
            </Button>
        </form>

        <p v-if="form.errors.reason" class="text-sm text-destructive">
            {{ form.errors.reason }}
        </p>
    </div>
</template>
