<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Users } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListingOwnerCard } from '../types';

type InquiryStatus =
    | 'new'
    | 'contacted'
    | 'accepted'
    | 'declined'
    | 'expired'
    | 'cancelled';

type InquiryCard = {
    id: number;
    name: string;
    status: InquiryStatus;
    status_label: string;
    start_date_label: string;
    end_date_label: string;
    show_url: string;
};

type AdminCustomer = {
    id: number;
    name: string;
    email: string;
    listing_inquiries_count: number;
    show_url: string;
};

type BlockedDate = {
    date: string;
    label: string;
};

const props = defineProps<{
    listing: ListingOwnerCard & { description: string | null };
    stats: {
        inquiries: number;
        new_inquiries: number;
        accepted_inquiries: number;
        upcoming_reservations: number;
        customers: number;
        blocked_dates_30d: number;
    };
    upcomingReservations: InquiryCard[];
    recentInquiries: InquiryCard[];
    customers: AdminCustomer[];
    blockedDates: BlockedDate[];
    urls: {
        index: string;
        manage: string;
        edit: string;
        bookings: string;
        customers: string;
        availability: string;
        public: string | null;
    };
}>();

defineOptions({
    layout: (props: {
        listing: { name: string };
        urls: {
            index: string;
            manage: string;
            edit: string;
            bookings: string;
            customers: string;
            availability: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Administración' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Reservaciones', href: props.urls.bookings },
            { title: 'Clientes', href: props.urls.customers },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Fotos y datos', href: props.urls.edit },
        ],
    }),
});

function statusVariant(
    status: InquiryStatus,
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
    <Head :title="`Administrar ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="overflow-hidden rounded-xl border bg-card">
            <div class="aspect-[3/1] bg-muted sm:aspect-[4/1]">
                <img
                    v-if="listing.cover_url"
                    :src="listing.cover_url"
                    :alt="listing.name"
                    class="h-full w-full object-cover"
                />
            </div>
            <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            {{ listing.name }}
                        </h1>
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
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            [listing.city, listing.state]
                                .filter(Boolean)
                                .join(', ') || 'Sin ubicación'
                        }}
                        · {{ listing.images_count }} imágenes ·
                        {{ listing.videos_count }} videos
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="urls.edit">
                            <Pencil />
                            Editar información
                        </Link>
                    </Button>
                    <Button v-if="urls.public" variant="outline" as-child>
                        <a
                            :href="urls.public"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Ver pública
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="urls.index">Volver</Link>
                    </Button>
                </div>
            </div>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Solicitudes</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ stats.inquiries }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ stats.new_inquiries }} nuevas
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Aceptadas</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ stats.accepted_inquiries }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Próximas reservas</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ stats.upcoming_reservations }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Clientes</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ stats.customers }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ stats.blocked_dates_30d }} fechas bloqueadas
                </p>
            </div>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">
                                Próximas reservas
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Reservas aceptadas de esta terraza.
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="urls.bookings">Ver todas</Link>
                        </Button>
                    </div>
                    <div
                        v-if="upcomingReservations.length > 0"
                        class="mt-4 divide-y"
                    >
                        <article
                            v-for="reservation in upcomingReservations"
                            :key="reservation.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ reservation.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ reservation.start_date_label }} al
                                    {{ reservation.end_date_label }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="reservation.show_url">Ver</Link>
                            </Button>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        No hay reservas próximas.
                    </p>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">
                                Solicitudes recientes
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Últimas solicitudes recibidas para esta terraza.
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="urls.bookings">Abrir bandeja</Link>
                        </Button>
                    </div>
                    <div
                        v-if="recentInquiries.length > 0"
                        class="mt-4 divide-y"
                    >
                        <article
                            v-for="inquiry in recentInquiries"
                            :key="inquiry.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ inquiry.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ inquiry.start_date_label }} al
                                    {{ inquiry.end_date_label }}
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
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Todavía no hay solicitudes.
                    </p>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Clientes</h2>
                            <p class="text-sm text-muted-foreground">
                                Personas vinculadas a solicitudes de esta
                                terraza.
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="urls.customers">
                                <Users />
                                Ver clientes
                            </Link>
                        </Button>
                    </div>
                    <div v-if="customers.length > 0" class="mt-4 divide-y">
                        <article
                            v-for="customer in customers"
                            :key="customer.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ customer.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ customer.email }} ·
                                    {{ customer.listing_inquiries_count }}
                                    {{
                                        customer.listing_inquiries_count === 1
                                            ? 'solicitud'
                                            : 'solicitudes'
                                    }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="customer.show_url">Ver ficha</Link>
                            </Button>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Aún no hay clientes vinculados.
                    </p>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-xl border bg-card p-5">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <CalendarDays class="size-4" />
                        Disponibilidad
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Bloquea mantenimiento, limpieza o uso personal.
                    </p>
                    <Button class="mt-4 w-full" variant="outline" as-child>
                        <Link :href="urls.availability">Abrir calendario</Link>
                    </Button>
                    <div v-if="blockedDates.length > 0" class="mt-4 space-y-2">
                        <p class="text-xs font-medium">
                            Próximas no disponibles
                        </p>
                        <div
                            v-for="date in blockedDates"
                            :key="date.date"
                            class="flex items-center justify-between rounded-md border px-3 py-2 text-xs"
                        >
                            <span>{{ date.date }}</span>
                            <span class="text-muted-foreground">
                                {{ date.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5">
                    <h2 class="font-semibold">Acciones rápidas</h2>
                    <div class="mt-3 flex flex-col gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="urls.edit">Fotos y datos</Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="urls.bookings">Reservaciones</Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="urls.customers">Clientes</Link>
                        </Button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
