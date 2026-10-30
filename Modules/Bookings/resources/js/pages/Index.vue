<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Users } from '@lucide/vue';
import { reactive } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    BookingListing,
    InquiryCard,
    InquiryStatus,
    Paginated,
    StatusOption,
} from '../types';

const props = defineProps<{
    team: { name: string; slug: string };
    inquiries: Paginated<InquiryCard>;
    listings: Array<BookingListing & { availability_url: string }>;
    filters: {
        status?: string | null;
        listing?: string | null;
        search?: string | null;
    };
    statuses: StatusOption[];
    urls: {
        index: string;
        customers: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Solicitudes',
                href: '/',
            },
        ],
    },
});

const form = reactive({
    status: props.filters.status ?? '',
    listing: props.filters.listing ?? '',
    search: props.filters.search ?? '',
});

function search(): void {
    router.get(props.urls.index, { ...form }, { preserveScroll: true });
}

function statusVariant(
    status: InquiryStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (
        status === 'declined' ||
        status === 'cancelled' ||
        status === 'expired'
    ) {
        return 'destructive';
    }

    if (status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head title="Solicitudes" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Solicitudes</h1>
                <p class="text-sm text-muted-foreground">
                    Da seguimiento a las solicitudes de renta de
                    {{ team.name }}.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.customers">
                    <Users />
                    Clientes
                </Link>
            </Button>
        </div>

        <form
            class="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
            @submit.prevent="search"
        >
            <div class="grid gap-2">
                <Label for="status">Estado</Label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="listing">Terraza</Label>
                <select
                    id="listing"
                    v-model="form.listing"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="">Todas</option>
                    <option
                        v-for="listing in listings"
                        :key="listing.id"
                        :value="listing.slug"
                    >
                        {{ listing.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="search">Buscar</Label>
                <Input
                    id="search"
                    v-model="form.search"
                    placeholder="Nombre, correo o teléfono"
                />
            </div>
            <div class="flex items-end">
                <Button type="submit" class="w-full">Filtrar</Button>
            </div>
        </form>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-4">
                <div
                    v-if="inquiries.data.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center"
                >
                    <h2 class="font-medium">No hay solicitudes</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Cuando alguien solicite una terraza aparecerá aquí.
                    </p>
                </div>

                <article
                    v-for="inquiry in inquiries.data"
                    :key="inquiry.id"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h2 class="font-semibold">
                                <Link
                                    :href="inquiry.show_url"
                                    class="hover:underline"
                                >
                                    {{ inquiry.name }}
                                </Link>
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ inquiry.listing.name }} ·
                                {{ inquiry.start_date_label }} al
                                {{ inquiry.end_date_label }}
                                <span v-if="inquiry.guests">
                                    · {{ inquiry.guests }} invitados
                                </span>
                            </p>
                        </div>
                        <Badge :variant="statusVariant(inquiry.status)">
                            {{ inquiry.status_label }}
                        </Badge>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        <span class="text-muted-foreground">{{
                            inquiry.email
                        }}</span>
                        <span class="text-muted-foreground">{{
                            inquiry.phone
                        }}</span>
                        <Button size="sm" as-child>
                            <Link :href="inquiry.show_url">Ver detalle</Link>
                        </Button>
                    </div>
                </article>

                <PaginationLinks :links="inquiries.links" />
            </div>

            <aside class="space-y-3">
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="flex items-center gap-2 text-sm font-semibold">
                        <CalendarDays class="size-4" />
                        Disponibilidad
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Bloquea fechas para mantenimiento o uso personal.
                    </p>
                    <div class="mt-3 space-y-2">
                        <Link
                            v-for="listing in listings"
                            :key="listing.id"
                            :href="listing.availability_url"
                            class="block rounded-md border px-3 py-2 text-sm hover:bg-accent"
                        >
                            {{ listing.name }}
                        </Link>
                        <p
                            v-if="listings.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            Primero crea una terraza.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
