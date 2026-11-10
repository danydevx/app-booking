<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type TodayTask = {
    id: number;
    title: string;
    notes: string | null;
    due_at: string | null;
    priority: string;
    priority_label: string;
    assignee: string | null;
    is_overdue: boolean;
    can_manage: boolean;
    complete_url: string;
    customer_name: string;
    customer_url: string;
};

type UpcomingBooking = {
    id: number;
    name: string;
    listing: string;
    start_date: string;
    end_date: string;
    show_url: string;
};

type UnassignedCustomer = {
    id: number;
    name: string;
    email: string;
    show_url: string;
};

const props = defineProps<{
    team: { name: string; slug: string };
    mine: boolean;
    overdue: TodayTask[];
    todayTasks: TodayTask[];
    upcomingTasks: TodayTask[];
    upcomingBookings: UpcomingBooking[];
    unassignedCustomers: UnassignedCustomer[];
    unassignedCount: number;
    urls: {
        today: string;
        bookings: string;
        customers: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Solicitudes', href: '/' }, { title: 'Hoy' }],
    },
});

function toggleMine(): void {
    router.get(
        props.urls.today,
        { mine: props.mine ? 0 : 1 },
        { preserveScroll: true },
    );
}

function complete(task: TodayTask): void {
    router.patch(task.complete_url, {}, { preserveScroll: true });
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Hoy" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Hoy</h1>
                <p class="text-sm text-muted-foreground">
                    Tareas y reservas que requieren atención en
                    {{ team.name }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    :variant="mine ? 'default' : 'outline'"
                    @click="toggleMine"
                >
                    {{ mine ? 'Viendo mis tareas' : 'Solo mis tareas' }}
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.customers">Clientes</Link>
                </Button>
            </div>
        </div>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Vencidas</h2>
                        <p class="text-sm text-muted-foreground">
                            Tareas abiertas con fecha pasada.
                        </p>
                    </div>
                    <Badge variant="destructive">{{ overdue.length }}</Badge>
                </div>

                <div v-if="overdue.length > 0" class="mt-4 space-y-3">
                    <article
                        v-for="task in overdue"
                        :key="task.id"
                        class="rounded-lg border border-destructive/40 bg-destructive/5 p-3"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <p class="font-medium">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    <Link
                                        :href="task.customer_url"
                                        class="underline underline-offset-4"
                                    >
                                        {{ task.customer_name }}
                                    </Link>
                                    · {{ formatDate(task.due_at) }}
                                    <span v-if="task.assignee">
                                        · {{ task.assignee }}
                                    </span>
                                </p>
                            </div>
                            <Button
                                v-if="task.can_manage"
                                size="sm"
                                variant="outline"
                                @click="complete(task)"
                            >
                                Completar
                            </Button>
                        </div>
                    </article>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin tareas vencidas.
                </p>
            </div>

            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Para hoy</h2>
                        <p class="text-sm text-muted-foreground">
                            Tareas con vencimiento hoy.
                        </p>
                    </div>
                    <Badge variant="secondary">{{ todayTasks.length }}</Badge>
                </div>

                <div v-if="todayTasks.length > 0" class="mt-4 space-y-3">
                    <article
                        v-for="task in todayTasks"
                        :key="task.id"
                        class="rounded-lg border p-3"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <p class="font-medium">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    <Link
                                        :href="task.customer_url"
                                        class="underline underline-offset-4"
                                    >
                                        {{ task.customer_name }}
                                    </Link>
                                    · {{ formatDate(task.due_at) }}
                                    <span v-if="task.assignee">
                                        · {{ task.assignee }}
                                    </span>
                                </p>
                            </div>
                            <Button
                                v-if="task.can_manage"
                                size="sm"
                                variant="outline"
                                @click="complete(task)"
                            >
                                Completar
                            </Button>
                        </div>
                    </article>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin tareas para hoy.
                </p>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Próximos 7 días</h2>
                        <p class="text-sm text-muted-foreground">
                            Siguientes tareas programadas.
                        </p>
                    </div>
                    <Badge variant="outline">{{ upcomingTasks.length }}</Badge>
                </div>

                <div v-if="upcomingTasks.length > 0" class="mt-4 space-y-3">
                    <article
                        v-for="task in upcomingTasks"
                        :key="task.id"
                        class="rounded-lg border p-3"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <p class="font-medium">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    <Link
                                        :href="task.customer_url"
                                        class="underline underline-offset-4"
                                    >
                                        {{ task.customer_name }}
                                    </Link>
                                    · {{ formatDate(task.due_at) }}
                                    <span v-if="task.assignee">
                                        · {{ task.assignee }}
                                    </span>
                                </p>
                            </div>
                            <Button
                                v-if="task.can_manage"
                                size="sm"
                                variant="outline"
                                @click="complete(task)"
                            >
                                Completar
                            </Button>
                        </div>
                    </article>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin tareas próximas.
                </p>
            </div>

            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Próximas reservas</h2>
                        <p class="text-sm text-muted-foreground">
                            Reservas aceptadas en los próximos 7 días.
                        </p>
                    </div>
                    <Badge variant="outline">
                        {{ upcomingBookings.length }}
                    </Badge>
                </div>

                <div v-if="upcomingBookings.length > 0" class="mt-4 space-y-3">
                    <article
                        v-for="booking in upcomingBookings"
                        :key="booking.id"
                        class="rounded-lg border p-3"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <p class="font-medium">{{ booking.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ booking.listing }} ·
                                    {{ booking.start_date }} al
                                    {{ booking.end_date }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="booking.show_url">Ver</Link>
                            </Button>
                        </div>
                    </article>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Sin reservas próximas.
                </p>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Clientes sin responsable</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ unassignedCount }} clientes activos sin asignar.
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="`${urls.customers}?assigned=none`">
                        Ver todos
                    </Link>
                </Button>
            </div>

            <div
                v-if="unassignedCustomers.length > 0"
                class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="customer in unassignedCustomers"
                    :key="customer.id"
                    :href="customer.show_url"
                    class="rounded-lg border px-3 py-2 text-sm hover:underline"
                >
                    <p class="font-medium">{{ customer.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ customer.email }}
                    </p>
                </Link>
            </div>
            <p v-else class="mt-4 text-sm text-muted-foreground">
                Todos los clientes tienen responsable.
            </p>
        </section>
    </div>
</template>
