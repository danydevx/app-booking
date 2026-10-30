<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CustomerCard, Paginated } from '../../types';

const props = defineProps<{
    team: { name: string; slug: string };
    customers: Paginated<CustomerCard>;
    filters: { search?: string };
    urls: {
        bookings: string;
        index: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Solicitudes',
                href: '/',
            },
            {
                title: 'Clientes',
            },
        ],
    },
});

const form = reactive({
    search: props.filters.search ?? '',
});

function search(): void {
    router.get(props.urls.index, { ...form }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Clientes" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Clientes</h1>
                <p class="text-sm text-muted-foreground">
                    Personas que han solicitado una terraza de {{ team.name }}.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.bookings">Solicitudes</Link>
            </Button>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="search">
            <div class="grid min-w-64 flex-1 gap-2">
                <Label for="search">Buscar</Label>
                <Input
                    id="search"
                    v-model="form.search"
                    placeholder="Nombre, correo o teléfono"
                />
            </div>
            <Button type="submit">Buscar</Button>
        </form>

        <div
            v-if="customers.data.length === 0"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <h2 class="font-medium">No hay clientes todavía</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Se crean automáticamente al recibir una solicitud.
            </p>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="customer in customers.data"
                :key="customer.id"
                class="rounded-xl border bg-card p-4"
            >
                <h2 class="font-semibold">
                    <Link :href="customer.show_url" class="hover:underline">
                        {{ customer.name }}
                    </Link>
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ customer.email }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ customer.phone }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ customer.inquiries_count }}
                    {{
                        customer.inquiries_count === 1
                            ? 'solicitud'
                            : 'solicitudes'
                    }}
                </p>
                <Button class="mt-3" size="sm" variant="outline" as-child>
                    <Link :href="customer.show_url">Ver ficha</Link>
                </Button>
            </article>
        </div>

        <PaginationLinks :links="customers.links" />
    </div>
</template>
