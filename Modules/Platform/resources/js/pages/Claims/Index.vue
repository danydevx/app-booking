<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BadgeCheck, FileText } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PaginationLinks from '@/components/PaginationLinks.vue';

type ClaimRow = {
    id: number;
    status: string;
    status_label: string;
    created_at: string | null;
    business_name: string;
    claimant_name: string;
    claimant_email: string;
    claimant_phone: string;
    role_label: string;
    has_evidence: boolean;
    listing: {
        id: number | null;
        name: string | null;
        slug: string | null;
        city: string | null;
        state: string | null;
        is_claimable: boolean;
    };
    target_team: { id: number; name: string } | null;
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
    claims: Paginated<ClaimRow>;
    counts: { pending: number; approved: number; rejected: number };
    filters: { q: string; status: string | null };
    statuses: Array<{ value: string; label: string }>;
    urls: {
        index: string;
        dashboard: string;
    };
}>();

const search = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');

function submit(): void {
    router.get(
        props.urls.index,
        {
            q: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function statusVariant(
    value: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (value === 'approved') {
        return 'default';
    }

    if (value === 'rejected') {
        return 'destructive';
    }

    return 'secondary';
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function location(claim: ClaimRow): string {
    return (
        [claim.listing.city, claim.listing.state].filter(Boolean).join(', ') ||
        'Sin ubicación'
    );
}
</script>

<template>
    <Head title="Reclamaciones · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Reclamaciones</h1>
                <p class="text-sm text-muted-foreground">
                    Revisa solicitudes de propietarios que quieren administrar
                    terrazas del Directorio.
                </p>
            </div>
            <Button as-child variant="outline">
                <Link :href="urls.dashboard">Volver al resumen</Link>
            </Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border bg-card p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Pendientes
                </p>
                <p class="mt-1 text-2xl font-semibold">{{ counts.pending }}</p>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Aprobadas
                </p>
                <p class="mt-1 text-2xl font-semibold">{{ counts.approved }}</p>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Rechazadas
                </p>
                <p class="mt-1 text-2xl font-semibold">{{ counts.rejected }}</p>
            </div>
        </div>

        <form
            class="flex flex-wrap items-end gap-3 rounded-lg border bg-card p-4"
            @submit.prevent="submit"
        >
            <div class="min-w-[14rem] flex-1 space-y-1">
                <label for="q" class="text-xs font-medium">Buscar</label>
                <Input
                    id="q"
                    v-model="search"
                    placeholder="Terraza, solicitante, correo o teléfono"
                />
            </div>
            <div class="space-y-1">
                <label for="status" class="text-xs font-medium">Estado</label>
                <select
                    id="status"
                    v-model="status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
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
            </div>
            <Button type="submit">Filtrar</Button>
        </form>

        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3">Terraza</th>
                        <th class="px-4 py-3">Solicitante</th>
                        <th class="px-4 py-3">Cuenta destino</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Recibida</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="claim in claims.data"
                        :key="claim.id"
                        class="border-t align-top"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">
                                {{ claim.listing.name ?? 'Terraza eliminada' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ location(claim) }}
                            </p>
                            <Badge
                                v-if="claim.listing.is_claimable"
                                variant="outline"
                                class="mt-2"
                            >
                                Sin reclamar
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ claim.claimant_name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ claim.claimant_email }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ claim.claimant_phone }} ·
                                {{ claim.role_label }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ claim.business_name }}</p>
                            <p
                                v-if="claim.target_team"
                                class="text-xs text-muted-foreground"
                            >
                                {{ claim.target_team.name }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Cuenta nueva al aprobar
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(claim.status)">
                                {{ claim.status_label }}
                            </Badge>
                            <p
                                v-if="claim.has_evidence"
                                class="mt-2 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <FileText class="size-3.5" />
                                Con documento
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            {{ formatDate(claim.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="`${urls.index}/${claim.id}`"
                                    aria-label="Revisar reclamación"
                                >
                                    <BadgeCheck class="size-4" />
                                    Revisar
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="claims.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-sm text-muted-foreground"
                        >
                            No hay reclamaciones con estos filtros.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationLinks :links="claims.links" />
    </div>
</template>
