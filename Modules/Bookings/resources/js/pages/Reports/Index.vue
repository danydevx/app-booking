<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Overview = {
    inquiries: number;
    contacted: number;
    accepted: number;
    declined: number;
    expired: number;
    conversion_percent: number;
    avg_first_response_hours: number | null;
    avg_accept_hours: number | null;
    customers: number;
    quotes: number;
    quotes_accepted: number;
    quote_conversion_percent: number;
    feedback_count: number;
    avg_feedback_rating: number | null;
};

type PipelineRow = {
    value: string;
    label: string;
    count: number;
};

type SourceRow = {
    source: string;
    count: number;
    accepted: number;
    conversion_percent: number;
};

type ListingRow = {
    listing: string;
    count: number;
    accepted: number;
    conversion_percent: number;
};

type MonthlyRow = {
    month: string;
    label: string;
    inquiries: number;
    accepted: number;
};

const props = defineProps<{
    team: { name: string; slug: string };
    filters: { from: string; to: string };
    overview: Overview;
    pipeline: PipelineRow[];
    sources: SourceRow[];
    listings: ListingRow[];
    monthly: MonthlyRow[];
    urls: {
        reports: string;
        bookings: string;
        customers: string;
        quotes: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Solicitudes', href: '/' },
            { title: 'Reportes' },
        ],
    },
});

const form = reactive({
    from: props.filters.from,
    to: props.filters.to,
});

function apply(): void {
    router.get(props.urls.reports, { ...form }, { preserveScroll: true });
}

function formatHours(value: number | null): string {
    return value === null ? '—' : `${value.toFixed(1)} h`;
}

function barWidth(value: number, max: number): string {
    if (max <= 0) {
        return '0%';
    }

    return `${Math.max(2, Math.round((value / max) * 100))}%`;
}

function maxOf(values: number[]): number {
    return Math.max(1, ...values);
}
</script>

<template>
    <Head title="Reportes" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Reportes</h1>
                <p class="text-sm text-muted-foreground">
                    Embudo, origen de leads y tiempos de respuesta de
                    {{ team.name }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.quotes">Cotizaciones</Link>
                </Button>
            </div>
        </div>

        <form
            class="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4"
            @submit.prevent="apply"
        >
            <div class="grid gap-2">
                <Label for="from">Desde</Label>
                <Input id="from" v-model="form.from" type="date" />
            </div>
            <div class="grid gap-2">
                <Label for="to">Hasta</Label>
                <Input id="to" v-model="form.to" type="date" />
            </div>
            <Button type="submit">Aplicar</Button>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Solicitudes</p>
                <p class="mt-1 text-3xl font-semibold">
                    {{ overview.inquiries }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ overview.accepted }} aceptadas ·
                    {{ overview.conversion_percent }}% conversión
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">1ª respuesta</p>
                <p class="mt-1 text-3xl font-semibold">
                    {{ formatHours(overview.avg_first_response_hours) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Promedio para marcar contacto
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Cierre</p>
                <p class="mt-1 text-3xl font-semibold">
                    {{ formatHours(overview.avg_accept_hours) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Promedio hasta aceptar
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Cotizaciones</p>
                <p class="mt-1 text-3xl font-semibold">
                    {{ overview.quotes }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ overview.quotes_accepted }} aceptadas ·
                    {{ overview.quote_conversion_percent }}%
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Opinión</p>
                <p class="mt-1 text-3xl font-semibold">
                    {{
                        overview.avg_feedback_rating !== null
                            ? `${overview.avg_feedback_rating}/5`
                            : '—'
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ overview.feedback_count }} reseñas
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">Embudo de clientes</h2>
                <p class="text-sm text-muted-foreground">
                    Distribución actual por etapa del pipeline.
                </p>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="stage in pipeline"
                        :key="stage.value"
                        class="space-y-1"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ stage.label }}</span>
                            <span class="text-muted-foreground">
                                {{ stage.count }}
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: barWidth(
                                        stage.count,
                                        maxOf(
                                            pipeline.map((item) => item.count),
                                        ),
                                    ),
                                }"
                            />
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-xs text-muted-foreground">
                    {{ overview.customers }} clientes activos.
                </p>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">Origen de solicitudes</h2>
                <p class="text-sm text-muted-foreground">
                    UTM, referencia o canal registrado.
                </p>
                <div v-if="sources.length > 0" class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-muted-foreground"
                            >
                                <th class="py-2">Origen</th>
                                <th class="py-2 text-right">Leads</th>
                                <th class="py-2 text-right">Aceptadas</th>
                                <th class="py-2 text-right">Conversión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="source in sources"
                                :key="source.source"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">{{ source.source }}</td>
                                <td class="py-2 text-right">
                                    {{ source.count }}
                                </td>
                                <td class="py-2 text-right">
                                    {{ source.accepted }}
                                </td>
                                <td class="py-2 text-right">
                                    {{ source.conversion_percent }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin datos de origen en el periodo.
                </p>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">Por terraza</h2>
                <div v-if="listings.length > 0" class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-muted-foreground"
                            >
                                <th class="py-2">Terraza</th>
                                <th class="py-2 text-right">Leads</th>
                                <th class="py-2 text-right">Aceptadas</th>
                                <th class="py-2 text-right">Conversión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="listing in listings"
                                :key="listing.listing"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">{{ listing.listing }}</td>
                                <td class="py-2 text-right">
                                    {{ listing.count }}
                                </td>
                                <td class="py-2 text-right">
                                    {{ listing.accepted }}
                                </td>
                                <td class="py-2 text-right">
                                    {{ listing.conversion_percent }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin solicitudes en el periodo.
                </p>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">Últimos 6 meses</h2>
                <div v-if="monthly.length > 0" class="mt-4 space-y-3">
                    <div
                        v-for="month in monthly"
                        :key="month.month"
                        class="space-y-1"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="capitalize">{{ month.label }}</span>
                            <span class="text-muted-foreground">
                                {{ month.inquiries }} leads ·
                                {{ month.accepted }} aceptadas
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: barWidth(
                                        month.inquiries,
                                        maxOf(
                                            monthly.map(
                                                (item) => item.inquiries,
                                            ),
                                        ),
                                    ),
                                }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin actividad reciente.
                </p>
            </section>
        </div>
    </div>
</template>
