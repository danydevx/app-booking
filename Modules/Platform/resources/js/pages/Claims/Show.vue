<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ExternalLink,
    FileText,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Claim = {
    id: number;
    status: string;
    status_label: string;
    business_name: string;
    role: string;
    role_label: string;
    claimant_name: string;
    claimant_email: string;
    claimant_phone: string;
    whatsapp_phone: string | null;
    message: string;
    has_evidence: boolean;
    evidence_name: string | null;
    evidence_size: number | null;
    rejection_reason: string | null;
    created_at: string | null;
    reviewed_at: string | null;
    reviewed_by: string | null;
    listing: {
        id: number | null;
        name: string | null;
        slug: string | null;
        city: string | null;
        state: string | null;
        address: string | null;
        status: string;
        status_label: string;
        is_claimable: boolean;
        is_claimed: boolean;
        public_url: string | null;
        platform_url: string | null;
        team: {
            id: number | null;
            name: string | null;
            is_directory: boolean;
        };
    };
    target_team: {
        id: number;
        name: string;
        platform_url: string;
    } | null;
    claimant: {
        id: number;
        name: string;
        email: string;
        platform_url: string;
    } | null;
};

const props = defineProps<{
    claim: Claim;
    can: { manage: boolean };
    urls: {
        index: string;
        approve: string;
        reject: string;
        evidence: string | null;
        listing: string | null;
    };
}>();

const rejectForm = useForm({
    reason: '',
});

const rejectError = computed(
    () => (rejectForm.errors as Record<string, string | undefined>).claim ?? '',
);

function approve(): void {
    if (
        !window.confirm(
            `¿Aprobar la reclamación de ${props.claim.claimant_name} y transferir la terraza?`,
        )
    ) {
        return;
    }

    router.post(props.urls.approve);
}

function reject(): void {
    rejectForm.post(props.urls.reject, {
        preserveScroll: true,
        onSuccess: () => rejectForm.reset(),
    });
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

function formatBytes(value: number | null): string {
    if (!value) {
        return '';
    }

    if (value >= 1024 * 1024) {
        return `${(value / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(value / 1024))} KB`;
}

const location =
    [props.claim.listing.city, props.claim.listing.state]
        .filter(Boolean)
        .join(', ') || 'Sin ubicación';
</script>

<template>
    <Head :title="`Reclamación #${claim.id} · Plataforma`" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <Link
                    :href="urls.index"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Reclamaciones
                </Link>
                <h1 class="text-2xl font-semibold">
                    Reclamación #{{ claim.id }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ formatDate(claim.created_at) }} ·
                    {{ claim.business_name }}
                </p>
            </div>
            <Badge :variant="statusVariant(claim.status)" class="text-sm">
                {{ claim.status_label }}
            </Badge>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Solicitante</CardTitle>
                        <CardDescription>
                            {{ claim.claimant_name }} ·
                            {{ claim.role_label }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <p class="flex items-center gap-2">
                                <Mail class="size-4 text-muted-foreground" />
                                {{ claim.claimant_email }}
                            </p>
                            <p class="flex items-center gap-2">
                                <Phone class="size-4 text-muted-foreground" />
                                {{ claim.claimant_phone }}
                            </p>
                            <p
                                v-if="claim.whatsapp_phone"
                                class="flex items-center gap-2"
                            >
                                <Phone class="size-4 text-muted-foreground" />
                                WhatsApp: {{ claim.whatsapp_phone }}
                            </p>
                        </div>

                        <div
                            v-if="claim.claimant"
                            class="flex flex-wrap gap-2 border-t pt-3"
                        >
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="claim.claimant.platform_url"
                                    target="_blank"
                                >
                                    Ver usuario
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Verificación declarada</CardTitle>
                        <CardDescription>
                            Información proporcionada por el solicitante.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <p class="whitespace-pre-line">{{ claim.message }}</p>

                        <div
                            v-if="claim.has_evidence && urls.evidence"
                            class="flex items-center justify-between gap-3 rounded-md border bg-muted/40 p-3"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <FileText class="size-4 shrink-0" />
                                <span class="truncate">
                                    {{ claim.evidence_name || 'Documento' }}
                                    <span
                                        v-if="claim.evidence_size"
                                        class="text-xs text-muted-foreground"
                                    >
                                        ({{ formatBytes(claim.evidence_size) }})
                                    </span>
                                </span>
                            </span>
                            <Button as-child size="sm" variant="outline">
                                <a :href="urls.evidence" target="_blank">
                                    <ExternalLink class="size-4" />
                                    Abrir
                                </a>
                            </Button>
                        </div>

                        <p
                            v-if="claim.rejection_reason"
                            class="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-destructive"
                        >
                            Motivo de rechazo: {{ claim.rejection_reason }}
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="claim.status === 'pending' && can.manage">
                    <CardHeader>
                        <CardTitle>Resolver reclamación</CardTitle>
                        <CardDescription>
                            Aprueba para transferir la terraza o rechaza con un
                            motivo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div
                            class="flex items-start gap-3 rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground"
                        >
                            <ShieldCheck class="mt-0.5 size-4 shrink-0" />
                            <p>
                                Al aprobar, la terraza pasa a
                                <strong class="text-foreground">{{
                                    claim.target_team?.name ??
                                    claim.business_name
                                }}</strong>
                                y se conserva la misma URL pública. Las
                                solicitudes previas también se transfieren.
                            </p>
                        </div>

                        <Button class="w-full" @click="approve">
                            Aprobar y transferir terraza
                        </Button>

                        <form
                            class="space-y-2 border-t pt-4"
                            @submit.prevent="reject"
                        >
                            <label for="reason" class="text-sm font-medium">
                                Motivo de rechazo
                            </label>
                            <textarea
                                id="reason"
                                v-model="rejectForm.reason"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Explica qué información falta o por qué no se pudo verificar la propiedad."
                            />
                            <p
                                v-if="rejectForm.errors.reason"
                                class="text-sm text-destructive"
                            >
                                {{ rejectForm.errors.reason }}
                            </p>
                            <p
                                v-if="rejectError"
                                class="text-sm text-destructive"
                            >
                                {{ rejectError }}
                            </p>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="rejectForm.processing"
                            >
                                Rechazar reclamación
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card v-else-if="claim.status !== 'pending'">
                    <CardHeader>
                        <CardTitle>Resolución</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p>
                            Revisada por
                            {{ claim.reviewed_by ?? 'el equipo de plataforma' }}
                            el {{ formatDate(claim.reviewed_at) }}.
                        </p>
                        <p
                            v-if="claim.rejection_reason"
                            class="text-muted-foreground"
                        >
                            Motivo: {{ claim.rejection_reason }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Terraza</CardTitle>
                        <CardDescription>{{ location }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div>
                            <p class="text-lg font-semibold">
                                {{ claim.listing.name }}
                            </p>
                            <p
                                v-if="claim.listing.address"
                                class="flex items-start gap-1 text-muted-foreground"
                            >
                                <MapPin class="mt-0.5 size-3.5 shrink-0" />
                                {{ claim.listing.address }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Badge variant="outline">
                                {{ claim.listing.status_label }}
                            </Badge>
                            <Badge
                                v-if="claim.listing.is_claimable"
                                variant="secondary"
                            >
                                Sin reclamar
                            </Badge>
                            <Badge
                                v-else-if="claim.listing.is_claimed"
                                variant="default"
                            >
                                Reclamada
                            </Badge>
                        </div>

                        <div class="space-y-1 text-muted-foreground">
                            <p>
                                Cuenta actual:
                                {{ claim.listing.team.name ?? '—' }}
                                <span v-if="claim.listing.team.is_directory">
                                    (Directorio)
                                </span>
                            </p>
                            <p v-if="claim.target_team">
                                Cuenta destino: {{ claim.target_team.name }}
                            </p>
                            <p v-else>
                                Cuenta destino: se creará «{{
                                    claim.business_name
                                }}» al aprobar.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-if="claim.listing.public_url"
                                as-child
                                size="sm"
                                variant="outline"
                            >
                                <a
                                    :href="claim.listing.public_url"
                                    target="_blank"
                                >
                                    <ExternalLink class="size-4" />
                                    Ver pública
                                </a>
                            </Button>
                            <Button
                                v-if="urls.listing"
                                as-child
                                size="sm"
                                variant="outline"
                            >
                                <Link :href="urls.listing">
                                    Ver en plataforma
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
