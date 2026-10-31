<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    updateUrl: string;
    status: string;
    resolutionAction: string | null;
    resolutionNote: string | null;
    resolutions: Array<{ value: string; label: string }>;
}>();

const form = useForm({
    status: ['resolved', 'dismissed'].includes(props.status)
        ? props.status
        : 'reviewing',
    resolution_action: props.resolutionAction ?? '',
    resolution_note: props.resolutionNote ?? '',
});

const requiresResolution = computed(
    () => form.status === 'resolved' || form.status === 'dismissed',
);

function submit(): void {
    form.patch(props.updateUrl, { preserveScroll: true });
}
</script>

<template>
    <form class="mt-3 space-y-2 border-t pt-3" @submit.prevent="submit">
        <div class="flex flex-wrap items-center gap-2">
            <select
                v-model="form.status"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                aria-label="Estado del reporte"
            >
                <option value="reviewing">En revisión</option>
                <option value="resolved">Resuelto</option>
                <option value="dismissed">Descartado</option>
            </select>

            <select
                v-if="requiresResolution"
                v-model="form.resolution_action"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                aria-label="Acción de resolución"
            >
                <option value="">Acción…</option>
                <option
                    v-for="resolution in resolutions"
                    :key="resolution.value"
                    :value="resolution.value"
                >
                    {{ resolution.label }}
                </option>
            </select>

            <Button type="submit" size="sm" :disabled="form.processing">
                Guardar
            </Button>
        </div>

        <textarea
            v-if="requiresResolution"
            v-model="form.resolution_note"
            rows="2"
            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="Nota de resolución obligatoria"
            aria-label="Nota de resolución"
        />

        <p v-if="form.errors.status" class="text-sm text-destructive">
            {{ form.errors.status }}
        </p>
        <p v-if="form.errors.resolution_action" class="text-sm text-destructive">
            {{ form.errors.resolution_action }}
        </p>
        <p v-if="form.errors.resolution_note" class="text-sm text-destructive">
            {{ form.errors.resolution_note }}
        </p>
    </form>
</template>
