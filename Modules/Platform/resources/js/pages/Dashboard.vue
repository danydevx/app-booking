<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CreditCard,
    Flag,
    History,
    Layers,
    Palette,
    Server,
    ShieldAlert,
    UserCog,
    Users,
} from '@lucide/vue';
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

const navigationGroups = [
    {
        title: 'Operación',
        items: [
            {
                title: 'Cuentas',
                description: 'Altas, miembros y estado',
                href: '/app/platform/accounts',
                icon: Users,
            },
            {
                title: 'Usuarios',
                description: 'Accesos y roles',
                href: '/app/platform/users',
                icon: UserCog,
            },
            {
                title: 'Terrazas',
                description: 'Publicaciones y moderación',
                href: '/app/platform/listings',
                icon: Building2,
            },
            {
                title: 'Suscripciones',
                description: 'Estado de cobros',
                href: '/app/platform/subscriptions',
                icon: CreditCard,
            },
            {
                title: 'Reportes',
                description: 'Cola de revisión',
                href: '/app/platform/reports',
                icon: Flag,
            },
            {
                title: 'Auditoría',
                description: 'Actividad de superadmins',
                href: '/app/platform/activity',
                icon: History,
            },
        ],
    },
    {
        title: 'Configuración',
        items: [
            {
                title: 'Planes',
                description: 'Límites y features',
                href: '/app/platform/plans',
                icon: Layers,
            },
            {
                title: 'Marca',
                description: 'Logo, remitente y legal',
                href: '/app/platform/branding',
                icon: Palette,
            },
            {
                title: 'Sistema',
                description: 'Colas y trabajos fallidos',
                href: '/app/platform/system',
                icon: Server,
            },
        ],
    },
];

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

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Accesos rápidos</h2>
                    <p class="text-sm text-muted-foreground">
                        Entra directamente a cada área de la plataforma.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div
                    v-for="group in navigationGroups"
                    :key="group.title"
                    class="rounded-xl border bg-card p-4"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ group.title }}
                    </p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <component :is="item.icon" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium">
                                    {{ item.title }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ item.description }}
                                </span>
                            </span>
                            <ArrowRight
                                class="size-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

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
