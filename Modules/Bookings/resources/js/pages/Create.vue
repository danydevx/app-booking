<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ListingOption = {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    capacity: number | null;
};

const props = defineProps<{
    team: { name: string; slug: string };
    listings: ListingOption[];
    activeListing: { id: number; name: string; slug: string } | null;
    management: {
        manage: string;
        edit: string;
        bookings: string;
        customers: string;
        availability: string;
        listings: string;
    } | null;
    urls: {
        index: string;
        store: string;
    };
}>();

defineOptions({
    layout: (props: {
        urls: { index: string };
        activeListing: { name: string } | null;
        management: {
            manage: string;
            edit: string;
            bookings: string;
            customers: string;
            availability: string;
            listings: string;
        } | null;
    }) => ({
        breadcrumbs:
            props.activeListing && props.management
                ? [
                      { title: 'Terrazas', href: props.management.listings },
                      {
                          title: props.activeListing.name,
                          href: props.management.manage,
                      },
                      {
                          title: 'Reservaciones',
                          href: props.management.bookings,
                      },
                      { title: 'Nueva reserva' },
                  ]
                : [
                      { title: 'Solicitudes', href: props.urls.index },
                      { title: 'Nueva reserva' },
                  ],
        submenu:
            props.activeListing && props.management
                ? [
                      { title: 'Resumen', href: props.management.manage },
                      {
                          title: 'Reservaciones',
                          href: props.management.bookings,
                      },
                      { title: 'Clientes', href: props.management.customers },
                      {
                          title: 'Disponibilidad',
                          href: props.management.availability,
                      },
                      { title: 'Fotos y datos', href: props.management.edit },
                  ]
                : [],
    }),
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    listing_id: props.listings[0]?.id ?? '',
    name: '',
    email: '',
    phone: '',
    whatsapp_phone: '',
    start_date: '',
    end_date: '',
    event_type: '',
    guests: '',
    message: '',
    notify_customer: true,
    dates: '',
});

function submit(): void {
    form.post(props.urls.store);
}
</script>

<template>
    <Head title="Nueva reserva" />

    <div class="flex flex-col gap-6 p-4">
        <div>
            <Link
                :href="urls.index"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Solicitudes
            </Link>
            <h1 class="mt-1 text-2xl font-semibold">
                Registrar reserva manual
            </h1>
            <p class="text-sm text-muted-foreground">
                La reserva se crea como aceptada para {{ team.name }} y bloquea
                las fechas seleccionadas.
            </p>
        </div>

        <div
            v-if="listings.length === 0"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <h2 class="font-medium">No hay terrazas disponibles</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Crea una terraza antes de registrar una reserva manual.
            </p>
            <Button class="mt-4" variant="outline" as-child>
                <Link :href="urls.index">Volver</Link>
            </Button>
        </div>

        <form
            v-else
            class="grid w-full gap-4 rounded-xl border bg-card p-5"
            @submit.prevent="submit"
        >
            <div v-if="activeListing" class="grid gap-2">
                <Label>Terraza</Label>
                <p class="text-sm font-medium">{{ activeListing.name }}</p>
            </div>
            <div v-else class="grid gap-2">
                <Label for="listing">Terraza</Label>
                <select
                    id="listing"
                    v-model="form.listing_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="listing in listings"
                        :key="listing.id"
                        :value="listing.id"
                    >
                        {{ listing.name }}
                    </option>
                </select>
                <InputError :message="form.errors.listing_id" />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Nombre del cliente</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Correo</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="phone">Teléfono</Label>
                    <Input id="phone" v-model="form.phone" required />
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="whatsapp">WhatsApp</Label>
                    <Input id="whatsapp" v-model="form.whatsapp_phone" />
                    <InputError :message="form.errors.whatsapp_phone" />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="start_date">Inicio</Label>
                    <Input
                        id="start_date"
                        v-model="form.start_date"
                        type="date"
                        :min="today"
                        required
                    />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="end_date">Fin</Label>
                    <Input
                        id="end_date"
                        v-model="form.end_date"
                        type="date"
                        :min="form.start_date || today"
                        required
                    />
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>

            <InputError :message="form.errors.dates" />

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="event_type">Tipo de evento</Label>
                    <Input
                        id="event_type"
                        v-model="form.event_type"
                        placeholder="Cumpleaños, boda, reunión…"
                    />
                    <InputError :message="form.errors.event_type" />
                </div>
                <div class="grid gap-2">
                    <Label for="guests">Invitados</Label>
                    <Input
                        id="guests"
                        v-model="form.guests"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.guests" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="message">Notas internas</Label>
                <textarea
                    id="message"
                    v-model="form.message"
                    rows="3"
                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="form.errors.message" />
            </div>

            <label class="flex items-start gap-2 text-sm">
                <input
                    v-model="form.notify_customer"
                    type="checkbox"
                    class="mt-1 size-4 rounded border-input"
                />
                <span>
                    Enviar confirmación por correo al cliente
                    <span class="block text-xs text-muted-foreground">
                        Se enviará a {{ form.email || 'el correo indicado' }}.
                    </span>
                </span>
            </label>
            <InputError :message="form.errors.notify_customer" />

            <div class="flex flex-wrap justify-end gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="urls.index">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    Registrar reserva
                </Button>
            </div>
        </form>
    </div>
</template>
