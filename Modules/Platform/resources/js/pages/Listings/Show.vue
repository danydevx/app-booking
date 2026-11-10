<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ModerationForm from '../../components/ModerationForm.vue';

type MediaRow = {
    id: number;
    type: string;
    url: string;
};

type ReportRow = {
    id: number;
    reason: string;
    reason_label: string;
    status: string;
    status_label: string;
    message: string | null;
    created_at: string | null;
};

type ActivityRow = {
    id: number;
    description: string;
    subject_type: string;
    subject_name: string | null;
    causer: string | null;
    properties: Record<string, unknown>;
    created_at: string | null;
};

const props = defineProps<{
    listing: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        city: string | null;
        state: string | null;
        address: string | null;
        capacity: number | null;
        status: string;
        status_label: string;
        is_suspended: boolean;
        suspended_at: string | null;
        suspension_reason: string | null;
        published_at: string | null;
        public_url: string | null;
        team: {
            id: number | null;
            name: string | null;
            slug: string | null;
            is_suspended: boolean;
            platform_url: string | null;
        };
        media: MediaRow[];
    };
    reports: ReportRow[];
    activities: ActivityRow[];
    can: { moderate: boolean };
    urls: {
        index: string;
        suspend: string;
        restore: string;
    };
}>();

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function reasonOf(activity: ActivityRow): string {
    const reason = activity.properties.reason;

    return typeof reason === 'string' ? reason : '';
}
</script>

<template>
    <Head :title="`${props.listing.name} · Plataforma`" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <Link
                    :href="urls.index"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Terrazas
                </Link>
                <h1 class="mt-1 text-2xl font-semibold">
                    {{ listing.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        [listing.city, listing.state]
                            .filter(Boolean)
                            .join(', ') || 'Sin ubicación'
                    }}
                    · Publicada {{ formatDate(listing.published_at) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="
                        listing.status === 'published'
                            ? 'default'
                            : listing.status === 'suspended'
                              ? 'destructive'
                              : 'secondary'
                    "
                >
                    {{ listing.status_label }}
                </Badge>
                <Badge v-if="listing.team.is_suspended" variant="destructive">
                    Cuenta suspendida
                </Badge>
                <Button
                    v-if="listing.public_url"
                    size="sm"
                    variant="outline"
                    as-child
                >
                    <a
                        :href="listing.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver pública
                        <ExternalLink class="size-3.5" />
                    </a>
                </Button>
            </div>
        </div>

        <div
            v-if="listing.is_suspended"
            class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
        >
            <p class="font-medium">Terraza suspendida por moderación</p>
            <p class="text-muted-foreground">
                {{ listing.suspension_reason }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Suspendida el {{ formatDate(listing.suspended_at) }}. No aparece
                en el catálogo público.
            </p>
        </div>

        <ModerationForm
            v-if="can.moderate && !listing.is_suspended"
            :action="urls.suspend"
            title="Suspender terraza"
            description="Deja de mostrarse en el catálogo público de inmediato. El contenido se conserva y puede restaurarse."
            submit-label="Suspender"
            destructive
        />

        <ModerationForm
            v-if="can.moderate && listing.is_suspended"
            :action="urls.restore"
            title="Restaurar terraza"
            description="Vuelve al estado previo a la suspensión y se hace visible si estaba publicada."
            submit-label="Restaurar"
        />

        <div class="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Cuenta</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p class="font-medium">{{ listing.team.name ?? '—' }}</p>
                    <Link
                        v-if="listing.team.platform_url"
                        :href="listing.team.platform_url"
                        class="text-primary underline"
                    >
                        Ver cuenta
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Datos</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm text-muted-foreground">
                    <p>Capacidad: {{ listing.capacity ?? '—' }}</p>
                    <p>Dirección: {{ listing.address ?? '—' }}</p>
                    <p>{{ listing.media.length }} archivos de medios</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Reportes</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p class="text-muted-foreground">
                        {{ reports.length }} reportes recibidos.
                    </p>
                    <Link
                        href="/app/platform/reports"
                        class="text-primary underline"
                    >
                        Abrir cola de reportes
                    </Link>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Descripción</CardTitle>
            </CardHeader>
            <CardContent class="text-sm text-muted-foreground">
                <p class="whitespace-pre-line">
                    {{ listing.description ?? 'Sin descripción.' }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Medios</CardTitle>
                <CardDescription>
                    Revisa fotos y videos sin modificarlos.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="media in listing.media"
                        :key="media.id"
                        class="overflow-hidden rounded-lg border bg-muted"
                    >
                        <img
                            v-if="media.type === 'image'"
                            :src="media.url"
                            alt=""
                            class="aspect-video w-full object-cover"
                        />
                        <a
                            v-else
                            :href="media.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex aspect-video items-center justify-center text-xs text-primary underline"
                        >
                            Ver video
                        </a>
                    </div>
                    <p
                        v-if="listing.media.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Sin medios.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Reportes de esta terraza</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="report in reports"
                        :key="report.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <div>
                            <p class="font-medium">{{ report.reason_label }}</p>
                            <p class="text-muted-foreground">
                                {{ report.message ?? 'Sin mensaje.' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">{{
                                report.status_label
                            }}</Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(report.created_at) }}
                            </span>
                        </div>
                    </li>
                    <li
                        v-if="reports.length === 0"
                        class="py-2 text-muted-foreground"
                    >
                        No hay reportes para esta terraza.
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Auditoría de la terraza</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="activity in activities"
                        :key="activity.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <div>
                            <Badge variant="outline">
                                {{ activity.description }}
                            </Badge>
                            <span class="ml-2 text-muted-foreground">
                                {{ reasonOf(activity) }}
                            </span>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ activity.causer ?? 'Sistema' }} ·
                            {{ formatDate(activity.created_at) }}
                        </span>
                    </li>
                    <li
                        v-if="activities.length === 0"
                        class="py-2 text-muted-foreground"
                    >
                        Sin acciones de moderación.
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
