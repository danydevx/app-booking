<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InquiryDetail } from '../types';

const props = defineProps<{
    team: { name: string; slug: string };
    inquiry: InquiryDetail;
    urls: {
        show: string;
        update: string;
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
                title: 'Editar reserva',
            },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    name: props.inquiry.name,
    email: props.inquiry.email,
    phone: props.inquiry.phone,
    whatsapp_phone: props.inquiry.whatsapp_phone ?? '',
    start_date: props.inquiry.start_date,
    end_date: props.inquiry.end_date,
    event_type: props.inquiry.event_type ?? '',
    guests: props.inquiry.guests ?? '',
    message: props.inquiry.message ?? '',
    notify_customer: true,
    dates: '',
    status: '',
});

function submit(): void {
    form.patch(props.urls.update);
}
</script>

<template>
    <Head :title="`Editar reserva · ${inquiry.name}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div>
            <Link
                :href="urls.show"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Volver al detalle
            </Link>
            <h1 class="mt-1 text-2xl font-semibold">Editar reserva</h1>
            <p class="text-sm text-muted-foreground">
                {{ inquiry.listing.name }} · {{ team.name }}. Si cambias las
                fechas de una reserva aceptada, se liberan las anteriores y se
                validan las nuevas.
            </p>
        </div>

        <form
            class="grid gap-4 rounded-xl border bg-card p-5"
            @submit.prevent="submit"
        >
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
            <InputError :message="form.errors.status" />

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="event_type">Tipo de evento</Label>
                    <Input id="event_type" v-model="form.event_type" />
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
                    Notificar al cliente si cambian las fechas
                    <span class="block text-xs text-muted-foreground">
                        Solo aplica a reservas aceptadas.
                    </span>
                </span>
            </label>
            <InputError :message="form.errors.notify_customer" />

            <div class="flex flex-wrap justify-end gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="urls.show">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    Guardar cambios
                </Button>
            </div>
        </form>
    </div>
</template>
