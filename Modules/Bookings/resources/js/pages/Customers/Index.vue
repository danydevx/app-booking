<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
        store: string;
        export: string;
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

const exportUrl = computed(() => {
    const query = form.search.trim();

    return query === ''
        ? props.urls.export
        : `${props.urls.export}?search=${encodeURIComponent(query)}`;
});

const createOpen = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    whatsapp_phone: '',
    notes: '',
});

function handleCreateOpen(value: boolean): void {
    createOpen.value = value;

    if (!value) {
        createForm.reset();
        createForm.clearErrors();
    }
}

function createCustomer(): void {
    createForm.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
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
            <div class="flex flex-wrap gap-2">
                <Dialog :open="createOpen" @update:open="handleCreateOpen">
                    <DialogTrigger as-child>
                        <Button>Nuevo cliente</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-lg">
                        <form
                            class="space-y-4"
                            @submit.prevent="createCustomer"
                        >
                            <DialogHeader>
                                <DialogTitle>Nuevo cliente</DialogTitle>
                                <DialogDescription>
                                    Registra un cliente manualmente para
                                    {{ team.name }}.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-3">
                                <div class="grid gap-2">
                                    <Label for="customer-name">Nombre</Label>
                                    <Input
                                        id="customer-name"
                                        v-model="createForm.name"
                                        required
                                    />
                                    <InputError
                                        :message="createForm.errors.name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="customer-email">Correo</Label>
                                    <Input
                                        id="customer-email"
                                        v-model="createForm.email"
                                        type="email"
                                        required
                                    />
                                    <InputError
                                        :message="createForm.errors.email"
                                    />
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="customer-phone"
                                            >Teléfono</Label
                                        >
                                        <Input
                                            id="customer-phone"
                                            v-model="createForm.phone"
                                        />
                                        <InputError
                                            :message="createForm.errors.phone"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="customer-whatsapp"
                                            >WhatsApp</Label
                                        >
                                        <Input
                                            id="customer-whatsapp"
                                            v-model="createForm.whatsapp_phone"
                                        />
                                        <InputError
                                            :message="
                                                createForm.errors.whatsapp_phone
                                            "
                                        />
                                    </div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="customer-notes"
                                        >Notas internas</Label
                                    >
                                    <textarea
                                        id="customer-notes"
                                        v-model="createForm.notes"
                                        rows="3"
                                        class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                    <InputError
                                        :message="createForm.errors.notes"
                                    />
                                </div>
                            </div>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary" type="button">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    :disabled="createForm.processing"
                                >
                                    Crear cliente
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                <Button variant="outline" as-child>
                    <a :href="exportUrl">Exportar CSV</a>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
            </div>
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
                Se crean al recibir una solicitud o manualmente.
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
                    · {{ customer.accepted_inquiries_count }} aceptadas
                </p>
                <p
                    v-if="customer.open_tasks_count > 0"
                    class="mt-1 text-xs"
                    :class="
                        customer.overdue_tasks_count > 0
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ customer.open_tasks_count }}
                    {{ customer.open_tasks_count === 1 ? 'tarea' : 'tareas' }}
                    pendientes
                    <span v-if="customer.overdue_tasks_count > 0">
                        · {{ customer.overdue_tasks_count }} vencida{{
                            customer.overdue_tasks_count === 1 ? '' : 's'
                        }}
                    </span>
                </p>
                <Button class="mt-3" size="sm" variant="outline" as-child>
                    <Link :href="customer.show_url">Ver ficha</Link>
                </Button>
            </article>
        </div>

        <PaginationLinks :links="customers.links" />
    </div>
</template>
