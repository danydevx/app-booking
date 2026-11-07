<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type ActivityRow = {
    id: number;
    description: string;
    subject_type: string;
    subject_name: string | null;
    causer: string | null;
    properties: Record<string, unknown>;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    activities: Paginated<ActivityRow>;
    filters: { q: string };
    urls: {
        index: string;
        dashboard: string;
    };
}>();

const search = ref(props.filters.q ?? '');

function submit(): void {
    router.get(
        props.urls.index,
        { q: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function reasonOf(activity: ActivityRow): string | null {
    const reason = activity.properties.reason;

    return typeof reason === 'string' && reason !== '' ? reason : null;
}
</script>

<template>
    <Head title="Auditoría · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Auditoría de moderación</h1>
                <p class="text-sm text-muted-foreground">
                    Cambios de cuentas, terrazas, usuarios y reportes.
                </p>
            </div>

            <form
                class="flex w-full max-w-md items-center gap-2"
                @submit.prevent="submit"
            >
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar evento o tipo…"
                    aria-label="Buscar auditoría"
                />
                <Button type="submit" variant="outline">Buscar</Button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead
                    class="bg-muted/50 text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Evento</th>
                        <th class="px-4 py-3">Objeto</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="activity in activities.data" :key="activity.id">
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(activity.created_at) }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ activity.description }}
                        </td>
                        <td class="px-4 py-3">
                            {{ activity.subject_type }}
                            <span
                                v-if="activity.subject_name"
                                class="text-muted-foreground"
                            >
                                · {{ activity.subject_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ activity.causer ?? 'Sistema' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ reasonOf(activity) ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="activities.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No hay actividad registrada.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ activities.from ?? 0 }}–{{ activities.to ?? 0 }} de
                {{ activities.total }}
            </p>
            <PaginationLinks :links="activities.links" />
        </div>
    </div>
</template>
