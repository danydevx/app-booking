<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Flag } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ReportResolutionForm from '../../components/ReportResolutionForm.vue';

type ReportRow = {
    id: number;
    reason: string;
    reason_label: string;
    status: string;
    status_label: string;
    message: string | null;
    reporter_email: string | null;
    resolution_action: string | null;
    resolution_action_label: string | null;
    resolution_note: string | null;
    handled_by: string | null;
    handled_at: string | null;
    created_at: string | null;
    update_url: string;
    listing: {
        id: number | null;
        name: string | null;
        slug: string | null;
        status: string | null;
        url: string | null;
    };
    account: {
        name: string | null;
        slug: string | null;
        url: string | null;
    };
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
    reports: Paginated<ReportRow>;
    filters: { status: string | null };
    statuses: Array<{ value: string; label: string }>;
    resolutions: Array<{ value: string; label: string }>;
    urls: {
        index: string;
        dashboard: string;
    };
}>();

const status = ref(props.filters.status ?? '');

function submit(): void {
    router.get(
        props.urls.index,
        { status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

function statusVariant(
    value: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (value === 'open') {
        return 'destructive';
    }

    if (value === 'reviewing') {
        return 'secondary';
    }

    if (value === 'resolved') {
        return 'default';
    }

    return 'outline';
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
</script>

<template>
    <Head title="Reportes · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Reportes</h1>
                <p class="text-sm text-muted-foreground">
                    Cola de reportes enviados desde el catálogo público.
                </p>
            </div>

            <form class="flex items-center gap-2" @submit.prevent="submit">
                <select
                    v-model="status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    aria-label="Filtrar por estado"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <Button type="submit" variant="outline">Filtrar</Button>
            </form>
        </div>

        <div v-if="reports.data.length === 0" class="rounded-xl border border-dashed p-10 text-center">
            <Flag class="mx-auto mb-2 size-6 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                No hay reportes para este filtro.
            </p>
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="report in reports.data"
                :key="report.id"
                class="rounded-xl border p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge :variant="statusVariant(report.status)">
                                {{ report.status_label }}
                            </Badge>
                            <span class="font-medium">{{ report.reason_label }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            <Link
                                v-if="report.listing.url"
                                :href="report.listing.url"
                                class="text-primary underline"
                            >
                                {{ report.listing.name ?? 'Terraza' }}
                            </Link>
                            <span v-else>Terraza eliminada</span>
                            ·
                            <Link
                                v-if="report.account.url"
                                :href="report.account.url"
                                class="text-primary underline"
                            >
                                {{ report.account.name ?? 'Cuenta' }}
                            </Link>
                        </p>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        {{ formatDate(report.created_at) }}
                    </span>
                </div>

                <p class="mt-2 text-sm">
                    {{ report.message ?? 'Sin mensaje del reportante.' }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Reportante: {{ report.reporter_email ?? 'anónimo' }}
                </p>

                <div
                    v-if="report.handled_at"
                    class="mt-2 rounded-md bg-muted/40 p-2 text-xs text-muted-foreground"
                >
                    {{ report.resolution_action_label ?? 'Resuelto' }} ·
                    {{ report.handled_by ?? 'Sistema' }} ·
                    {{ formatDate(report.handled_at) }}
                    <span v-if="report.resolution_note">
                        — {{ report.resolution_note }}
                    </span>
                </div>

                <ReportResolutionForm
                    :update-url="report.update_url"
                    :status="report.status"
                    :resolution-action="report.resolution_action"
                    :resolution-note="report.resolution_note"
                    :resolutions="resolutions"
                />
            </article>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Mostrando {{ reports.from ?? 0 }}–{{ reports.to ?? 0 }} de
                    {{ reports.total }}
                </p>
                <PaginationLinks :links="reports.links" />
            </div>
        </div>
    </div>
</template>
