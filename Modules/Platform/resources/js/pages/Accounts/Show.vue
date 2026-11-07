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

type MemberRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    joined_at: string | null;
};

type ListingRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    city: string | null;
    is_suspended: boolean;
    public_url: string | null;
    platform_url: string;
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
    account: {
        id: number;
        name: string;
        slug: string;
        timezone: string;
        created_at: string | null;
        is_suspended: boolean;
        suspended_at: string | null;
        suspension_reason: string | null;
        stripe_id: string | null;
        stripe_dashboard_url: string | null;
    };
    owner: { id: number; name: string; email: string } | null;
    members: MemberRow[];
    listings: ListingRow[];
    billing: {
        plan: string;
        plan_slug: string;
        status: string;
        stripe_status: string | null;
        ends_at: string | null;
        trial_ends_at: string | null;
        has_customer: boolean;
        listings_limit: number;
        dashboard_url: string | null;
    };
    activities: ActivityRow[];
    can: { suspend: boolean };
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
    <Head :title="`${props.account.name} · Plataforma`" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <Link
                    :href="urls.index"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Cuentas
                </Link>
                <h1 class="mt-1 text-2xl font-semibold">
                    {{ account.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    /{{ account.slug }} · Alta
                    {{ formatDate(account.created_at) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="account.is_suspended ? 'destructive' : 'default'"
                >
                    {{ account.is_suspended ? 'Suspendida' : 'Activa' }}
                </Badge>
                <Badge variant="outline">{{ billing.plan }}</Badge>
                <Badge variant="secondary">{{ billing.status }}</Badge>
            </div>
        </div>

        <div
            v-if="account.is_suspended"
            class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
        >
            <p class="font-medium">Cuenta suspendida</p>
            <p class="text-muted-foreground">
                {{ account.suspension_reason }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Suspendida el {{ formatDate(account.suspended_at) }}. Sus
                terrazas no aparecen en el catálogo público.
            </p>
        </div>

        <ModerationForm
            v-if="can.suspend && !account.is_suspended"
            :action="urls.suspend"
            title="Suspender cuenta"
            description="Oculta sus terrazas del catálogo y bloquea el acceso a las áreas de la cuenta. La información no se elimina."
            submit-label="Suspender"
            destructive
        />

        <ModerationForm
            v-if="can.suspend && account.is_suspended"
            :action="urls.restore"
            title="Restaurar cuenta"
            description="Devuelve el acceso a la cuenta y vuelve a mostrar sus terrazas publicadas."
            submit-label="Restaurar"
        />

        <div class="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Propietario</CardTitle>
                </CardHeader>
                <CardContent class="text-sm">
                    <template v-if="owner">
                        <p class="font-medium">{{ owner.name }}</p>
                        <p class="text-muted-foreground">{{ owner.email }}</p>
                    </template>
                    <p v-else class="text-muted-foreground">
                        Sin propietario registrado.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Facturación</CardTitle>
                    <CardDescription>Solo lectura.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p>
                        <span class="text-muted-foreground">Plan:</span>
                        {{ billing.plan }} · límite
                        {{ billing.listings_limit }} terrazas
                    </p>
                    <p>
                        <span class="text-muted-foreground">Stripe:</span>
                        {{ billing.stripe_status ?? 'Sin suscripción' }}
                    </p>
                    <p v-if="billing.ends_at">
                        <span class="text-muted-foreground">Termina:</span>
                        {{ billing.ends_at }}
                    </p>
                    <p v-if="billing.trial_ends_at">
                        <span class="text-muted-foreground">Prueba:</span>
                        {{ billing.trial_ends_at }}
                    </p>
                    <a
                        v-if="billing.dashboard_url"
                        :href="billing.dashboard_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-primary underline"
                    >
                        Abrir en Stripe
                        <ExternalLink class="size-3.5" />
                    </a>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Resumen</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm text-muted-foreground">
                    <p>{{ members.length }} miembros</p>
                    <p>{{ listings.length }} terrazas</p>
                    <p>Zona horaria: {{ account.timezone }}</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Miembros</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <div>
                            <p class="font-medium">{{ member.name }}</p>
                            <p class="text-muted-foreground">
                                {{ member.email }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">{{
                                member.role_label
                            }}</Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ member.joined_at ?? '—' }}
                            </span>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Terrazas</CardTitle>
                <CardDescription>
                    Revisa el estado de cada terraza antes de moderar.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="listing in listings"
                        :key="listing.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <div>
                            <p class="font-medium">{{ listing.name }}</p>
                            <p class="text-muted-foreground">
                                {{ listing.city ?? 'Sin ciudad' }}
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
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="listing.platform_url"
                                    >Revisar</Link
                                >
                            </Button>
                            <a
                                v-if="listing.public_url"
                                :href="listing.public_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-xs text-primary underline"
                            >
                                Pública
                            </a>
                        </div>
                    </li>
                    <li
                        v-if="listings.length === 0"
                        class="py-2 text-muted-foreground"
                    >
                        La cuenta no tiene terrazas.
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Auditoría de la cuenta</CardTitle>
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
