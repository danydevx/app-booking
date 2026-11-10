<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
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
    filters: {
        q: string;
        actor: string;
        from: string | null;
        to: string | null;
    };
    urls: {
        index: string;
        export: string;
        dashboard: string;
    };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    actor: props.filters.actor ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (form.q) {
        params.set('q', form.q);
    }

    if (form.actor) {
        params.set('actor', form.actor);
    }

    if (form.from) {
        params.set('from', form.from);
    }

    if (form.to) {
        params.set('to', form.to);
    }

    const query = params.toString();

    return query === '' ? props.urls.export : `${props.urls.export}?${query}`;
});

function submit(): void {
    router.get(
        props.urls.index,
        {
            q: form.q || undefined,
            actor: form.actor || undefined,
            from: form.from || undefined,
            to: form.to || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    form.q = '';
    form.actor = '';
    form.from = '';
    form.to = '';
    router.get(props.urls.index, {}, { preserveState: true, replace: true });
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
        <div class="space-y-3">
            <div>
                <h1 class="text-2xl font-semibold">Auditoría de moderación</h1>
                <p class="text-sm text-muted-foreground">
                    Cambios de cuentas, terrazas, usuarios y reportes.
                </p>
            </div>

            <form
                class="grid gap-2 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-6"
                @submit.prevent="submit"
            >
                <Input
                    v-model="form.q"
                    type="search"
                    placeholder="Evento o tipo…"
                    aria-label="Buscar evento"
                    class="lg:col-span-2"
                />
                <Input
                    v-model="form.actor"
                    type="search"
                    placeholder="Actor: nombre o correo"
                    aria-label="Filtrar por actor"
                    class="lg:col-span-2"
                />
                <Input
                    v-model="form.from"
                    type="date"
                    aria-label="Desde"
                    title="Desde"
                />
                <Input
                    v-model="form.to"
                    type="date"
                    aria-label="Hasta"
                    title="Hasta"
                />
                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-6">
                    <Button type="submit" variant="outline">Filtrar</Button>
                    <Button type="button" variant="ghost" @click="clearFilters">
                        Limpiar
                    </Button>
                    <Button variant="outline" as-child>
                        <a :href="exportUrl">Exportar CSV</a>
                    </Button>
                </div>
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
