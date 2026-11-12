<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CustomerQuoteCard, Paginated, StatusOption } from '../../types';

const props = defineProps<{
    team: { name: string; slug: string };
    quotes: Paginated<CustomerQuoteCard>;
    statuses: StatusOption[];
    listings: Array<{ id: number; name: string; slug: string }>;
    filters: {
        status?: string;
        search?: string;
        listing?: string;
        from?: string;
        to?: string;
    };
    urls: {
        index: string;
        create: string;
        bookings: string;
        customers: string;
        billing: string;
    };
}>();

defineOptions({
    layout: (props: { urls: { bookings: string } }) => ({
        breadcrumbs: [
            { title: 'Solicitudes', href: props.urls.bookings },
            { title: 'Cotizaciones' },
        ],
    }),
});

const form = reactive({
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
    listing: props.filters.listing ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function search(): void {
    router.get(props.urls.index, { ...form }, { preserveScroll: true });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (status === 'declined' || status === 'expired') {
        return 'destructive';
    }

    if (status === 'sent') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head title="Cotizaciones" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Cotizaciones</h1>
                <p class="text-sm text-muted-foreground">
                    Propuestas enviadas a clientes de {{ team.name }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child>
                    <Link :href="urls.create">Nueva cotización</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.customers">Clientes</Link>
                </Button>
            </div>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="search">
            <div class="grid min-w-64 flex-1 gap-2">
                <Label for="search">Buscar cliente</Label>
                <Input
                    id="search"
                    v-model="form.search"
                    placeholder="Nombre o correo"
                />
            </div>
            <div class="grid min-w-44 gap-2">
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
            <div class="grid min-w-44 gap-2">
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
                <Label for="from">Desde</Label>
                <Input id="from" v-model="form.from" type="date" />
            </div>
            <div class="grid gap-2">
                <Label for="to">Hasta</Label>
                <Input id="to" v-model="form.to" type="date" />
            </div>
            <Button type="submit">Buscar</Button>
        </form>

        <div
            v-if="quotes.data.length === 0"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <h2 class="font-medium">No hay cotizaciones</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Crea tu primera cotización y genera un PDF profesional para tu
                cliente.
            </p>
            <Button class="mt-4" as-child>
                <Link :href="urls.create">Nueva cotización</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="quote in quotes.data"
                :key="quote.id"
                class="rounded-xl border bg-card p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h2 class="font-semibold">
                        <Link :href="quote.show_url" class="hover:underline">
                            {{ quote.folio }} · {{ quote.customer.name }}
                        </Link>
                    </h2>
                    <Badge :variant="statusVariant(quote.status)">
                        {{ quote.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ quote.listing.name }}
                </p>
                <p class="mt-2 text-sm">
                    {{ quote.start_date_label }} al
                    {{ quote.end_date_label }}
                </p>
                <p class="mt-1 text-sm font-medium">
                    Total: {{ quote.total_formatted }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Vence:
                    {{
                        quote.expires_at
                            ? new Date(quote.expires_at).toLocaleDateString(
                                  'es-MX',
                              )
                            : 'Sin vencimiento'
                    }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="quote.show_url">Ver</Link>
                    </Button>
                    <Button
                        v-if="quote.can_edit"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <Link :href="quote.edit_url">Editar</Link>
                    </Button>
                    <Button
                        v-if="quote.can_download_pdf"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <a :href="quote.pdf_url">PDF</a>
                    </Button>
                    <Button
                        v-if="quote.can_duplicate"
                        size="sm"
                        variant="outline"
                        @click="router.post(quote.duplicate_url)"
                    >
                        Duplicar
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <a
                            :href="quote.public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Link público
                        </a>
                    </Button>
                </div>
            </article>
        </div>

        <PaginationLinks :links="quotes.links" />
    </div>
</template>
