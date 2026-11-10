<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { DashboardInvitation, Team } from '@/types';

type UpcomingBooking = {
    id: number;
    name: string;
    listing: string;
    start_date: string;
    end_date: string;
    show_url: string;
};

type RecentInquiry = {
    id: number;
    name: string;
    listing: string;
    status: string;
    status_label: string;
    created_at: string | null;
    show_url: string;
};

const props = defineProps<{
    pendingInvitations?: DashboardInvitation[];
    overview: {
        listings: number;
        published_listings: number;
        new_inquiries: number;
        upcoming_bookings: number;
        customers: number;
        open_tasks: number;
        overdue_tasks: number;
        occupancy_percent: number;
        conversion_percent: number;
        booked_days_30d: number;
        deposits_received_cents: number;
        currency: string;
    };
    upcomingBookings: UpcomingBooking[];
    recentInquiries: RecentInquiry[];
    urls: {
        listings: string;
        bookings: string;
        customers: string;
    };
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T12:00:00`).toLocaleDateString('es-MX', {
        dateStyle: 'medium',
    });
}

function formatMoney(cents: number, currency: string): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency,
    }).format(cents / 100);
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (['declined', 'cancelled', 'expired'].includes(status)) {
        return 'destructive';
    }

    if (status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div>
            <h1 class="text-2xl font-semibold">Panel de la cuenta</h1>
            <p class="text-sm text-muted-foreground">
                Actividad de terrazas, solicitudes y reservas.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Terrazas</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ props.overview.listings }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>{{ props.overview.published_listings }} publicadas</p>
                    <Link :href="urls.listings" class="underline">
                        Administrar terrazas
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Solicitudes nuevas</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ props.overview.new_inquiries }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    <Link :href="urls.bookings" class="underline">
                        Revisar bandeja
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Reservas próximas</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ props.overview.upcoming_bookings }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Fechas aceptadas por comenzar.
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Clientes</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ props.overview.customers }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>
                        Anticipos recibidos:
                        {{
                            formatMoney(
                                props.overview.deposits_received_cents,
                                props.overview.currency,
                            )
                        }}
                    </p>
                    <p
                        :class="
                            props.overview.overdue_tasks > 0
                                ? 'text-destructive'
                                : ''
                        "
                    >
                        {{ props.overview.open_tasks }} tareas pendientes ·
                        {{ props.overview.overdue_tasks }} vencidas
                    </p>
                    <Link :href="urls.customers" class="underline">
                        Ver clientes
                    </Link>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardDescription
                        >Ocupación próximos 30 días</CardDescription
                    >
                    <CardTitle class="text-3xl">
                        {{ props.overview.occupancy_percent }}%
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    {{ props.overview.booked_days_30d }} días reservados en
                    {{ props.overview.listings }}
                    {{
                        props.overview.listings === 1 ? 'terraza' : 'terrazas'
                    }}.
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription
                        >Conversión últimos 30 días</CardDescription
                    >
                    <CardTitle class="text-3xl">
                        {{ props.overview.conversion_percent }}%
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Solicitudes aceptadas sobre el total recibido.
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Próximas reservas</CardTitle>
                    <CardDescription>
                        Reservas aceptadas con fecha futura.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingBookings.length > 0" class="divide-y">
                        <div
                            v-for="booking in upcomingBookings"
                            :key="booking.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ booking.listing }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ booking.name }} ·
                                    {{ formatDate(booking.start_date) }} al
                                    {{ formatDate(booking.end_date) }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="booking.show_url">Ver</Link>
                            </Button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No hay reservas próximas.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Solicitudes recientes</CardTitle>
                    <CardDescription>
                        Últimas solicitudes recibidas.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recentInquiries.length > 0" class="divide-y">
                        <div
                            v-for="inquiry in recentInquiries"
                            :key="inquiry.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ inquiry.listing }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ inquiry.name }} ·
                                    {{ formatDate(inquiry.created_at) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="statusVariant(inquiry.status)">
                                    {{ inquiry.status_label }}
                                </Badge>
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="inquiry.show_url">Ver</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Todavía no hay solicitudes.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
