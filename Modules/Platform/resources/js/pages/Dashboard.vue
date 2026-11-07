<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Flag, ShieldAlert, Users } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type ActivityRow = {
    id: number;
    description: string;
    subject_type: string;
    subject_name: string | null;
    causer: string | null;
    created_at: string | null;
};

type SignupRow = {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
};

type SubscriptionRow = {
    id: number;
    team_name: string | null;
    stripe_status: string | null;
    ends_at: string | null;
    updated_at: string | null;
};

defineProps<{
    stats: {
        accounts: number;
        users: number;
        paid: number;
        free: number;
        canceled: number;
        listings: number;
        published_listings: number;
        pending_reports: number;
    };
    recentSignups: SignupRow[];
    recentSubscriptions: SubscriptionRow[];
    recentActivity: ActivityRow[];
    urls: {
        accounts: string;
        listings: string;
        subscriptions: string;
        reports: string;
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
</script>

<template>
    <Head title="Plataforma" />

    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-semibold">Resumen de plataforma</h1>
            <p class="text-sm text-muted-foreground">
                Revisión operativa de cuentas, suscripciones, terrazas y
                reportes.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Cuentas</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.accounts }}</CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    {{ stats.users }} usuarios registrados ·
                    <Link :href="urls.accounts" class="underline">
                        Revisar cuentas
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Con plan de pago</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.paid }}</CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Activas o en periodo de gracia.
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Plan Gratis</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.free }}</CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Sin suscripción registrada.
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Canceladas</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ stats.canceled }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Suscripciones terminadas o canceladas.
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardDescription>Terrazas publicadas</CardDescription>
                    <CardTitle class="text-2xl">
                        {{ stats.published_listings }}
                        <span class="text-base text-muted-foreground">
                            / {{ stats.listings }}
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    <Link :href="urls.listings" class="underline">
                        Revisar terrazas
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Reportes pendientes</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        {{ stats.pending_reports }}
                        <Flag
                            v-if="stats.pending_reports > 0"
                            class="size-5 text-destructive"
                        />
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    <Link :href="urls.reports" class="underline">
                        Abrir cola de reportes
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Suscripciones</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <ShieldAlert class="size-5 text-muted-foreground" />
                        Solo lectura
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    <Link :href="urls.subscriptions" class="underline">
                        Ver suscripciones
                    </Link>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Altas recientes</CardTitle>
                    <CardDescription
                        >Últimos usuarios registrados.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <ul class="divide-y text-sm">
                        <li
                            v-for="signup in recentSignups"
                            :key="signup.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div>
                                <p class="font-medium">{{ signup.name }}</p>
                                <p class="text-muted-foreground">
                                    {{ signup.email }}
                                </p>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(signup.created_at) }}
                            </span>
                        </li>
                        <li
                            v-if="recentSignups.length === 0"
                            class="py-2 text-muted-foreground"
                        >
                            Sin registros.
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Cambios de suscripción</CardTitle>
                    <CardDescription>
                        Updates recientes recibidos por la plataforma.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y text-sm">
                        <li
                            v-for="subscription in recentSubscriptions"
                            :key="subscription.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ subscription.team_name ?? 'Cuenta' }}
                                </p>
                                <p class="text-muted-foreground">
                                    {{ subscription.stripe_status ?? '—' }}
                                    <span v-if="subscription.ends_at">
                                        · termina
                                        {{ subscription.ends_at }}
                                    </span>
                                </p>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(subscription.updated_at) }}
                            </span>
                        </li>
                        <li
                            v-if="recentSubscriptions.length === 0"
                            class="py-2 text-muted-foreground"
                        >
                            Sin cambios registrados.
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Actividad de moderación</CardTitle>
                <CardDescription>
                    Últimas acciones auditadas por superadmins.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="activity in recentActivity"
                        :key="activity.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">
                                {{ activity.description }}
                            </Badge>
                            <span class="font-medium">
                                {{
                                    activity.subject_name ??
                                    activity.subject_type
                                }}
                            </span>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ activity.causer ?? 'Sistema' }} ·
                            {{ formatDate(activity.created_at) }}
                        </span>
                    </li>
                    <li
                        v-if="recentActivity.length === 0"
                        class="py-2 text-muted-foreground"
                    >
                        Sin actividad de moderación.
                    </li>
                </ul>
            </CardContent>
        </Card>

        <p class="flex items-center gap-2 text-xs text-muted-foreground">
            <Users class="size-3.5" />
            El área de plataforma no permite impersonar cuentas ni cambiar
            suscripciones fuera de Stripe.
        </p>
    </div>
</template>
