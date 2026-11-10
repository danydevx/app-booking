<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CustomerQuoteCard, StatusOption } from '../../types';

const props = defineProps<{
    quote: CustomerQuoteCard;
    statuses: StatusOption[];
    urls: {
        index: string;
        customer: string;
        booking: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Solicitudes', href: '/' },
            { title: 'Cotizaciones', href: '/' },
            { title: 'Detalle' },
        ],
    },
});

const form = useForm({
    listing_id: props.quote.listing.id,
    start_date: props.quote.start_date,
    end_date: props.quote.end_date,
    event_type: props.quote.event_type ?? '',
    guests: props.quote.guests ?? '',
    subtotal: (props.quote.subtotal_cents / 100).toFixed(2),
    deposit: (props.quote.deposit_cents / 100).toFixed(2),
    notes: props.quote.notes ?? '',
    terms: props.quote.terms ?? '',
    expires_at: props.quote.expires_at
        ? props.quote.expires_at.slice(0, 16)
        : '',
});

const statusVariant = computed<
    'default' | 'secondary' | 'destructive' | 'outline'
>(() => {
    if (props.quote.status === 'accepted') {
        return 'default';
    }

    if (props.quote.status === 'declined' || props.quote.status === 'expired') {
        return 'destructive';
    }

    if (props.quote.status === 'sent') {
        return 'secondary';
    }

    return 'outline';
});

function save(): void {
    form.patch(props.quote.update_url, { preserveScroll: true });
}

function sendQuote(): void {
    router.post(props.quote.send_url, {}, { preserveScroll: true });
}

function convert(): void {
    if (
        !window.confirm(
            '¿Convertir esta cotización en reserva? Se bloquearán las fechas.',
        )
    ) {
        return;
    }

    router.post(props.quote.convert_url);
}
</script>

<template>
    <Head :title="`Cotización · ${quote.customer.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">
                        Cotización · {{ quote.customer.name }}
                    </h1>
                    <Badge :variant="statusVariant">
                        {{ quote.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ quote.listing.name }} · {{ quote.start_date_label }} al
                    {{ quote.end_date_label }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="quote.can_send"
                    variant="outline"
                    @click="sendQuote"
                >
                    Enviar por correo
                </Button>
                <Button v-if="quote.can_convert" @click="convert">
                    Convertir en reserva
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="quote.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver link público
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.index">Volver</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Total</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.subtotal_formatted }} {{ quote.currency }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Anticipo</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.deposit_formatted }} {{ quote.currency }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Invitados</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.guests ?? '—' }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Vence</p>
                <p class="mt-1 text-sm font-medium">
                    {{
                        quote.expires_at
                            ? new Date(quote.expires_at).toLocaleDateString(
                                  'es-MX',
                                  { dateStyle: 'medium' },
                              )
                            : 'Sin vencimiento'
                    }}
                </p>
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            <div class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Detalle</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Cliente</dt>
                            <dd class="font-medium">
                                <Link
                                    :href="urls.customer"
                                    class="hover:underline"
                                >
                                    {{ quote.customer.name }}
                                </Link>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Correo</dt>
                            <dd>{{ quote.customer.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Terraza</dt>
                            <dd>{{ quote.listing.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Tipo de evento
                            </dt>
                            <dd>{{ quote.event_type ?? 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Enviada</dt>
                            <dd>
                                {{
                                    quote.sent_at
                                        ? new Date(
                                              quote.sent_at,
                                          ).toLocaleString('es-MX', {
                                              dateStyle: 'medium',
                                              timeStyle: 'short',
                                          })
                                        : 'Todavía no'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Aceptada</dt>
                            <dd>
                                {{
                                    quote.accepted_at
                                        ? new Date(
                                              quote.accepted_at,
                                          ).toLocaleString('es-MX', {
                                              dateStyle: 'medium',
                                              timeStyle: 'short',
                                          })
                                        : 'Todavía no'
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="quote.notes" class="mt-4">
                        <h3 class="text-sm font-medium">Notas</h3>
                        <p
                            class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ quote.notes }}
                        </p>
                    </div>
                    <div v-if="quote.terms" class="mt-4">
                        <h3 class="text-sm font-medium">Términos</h3>
                        <p
                            class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ quote.terms }}
                        </p>
                    </div>

                    <div v-if="urls.booking" class="mt-4">
                        <Button variant="outline" as-child>
                            <Link :href="urls.booking">Ver reserva</Link>
                        </Button>
                    </div>
                </section>

                <section
                    v-if="quote.can_edit"
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="text-lg font-semibold">Editar cotización</h2>
                    <form
                        class="mt-4 grid gap-4 sm:grid-cols-2"
                        @submit.prevent="save"
                    >
                        <div class="grid gap-2">
                            <Label for="start-date">Inicio</Label>
                            <Input
                                id="start-date"
                                v-model="form.start_date"
                                type="date"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end-date">Fin</Label>
                            <Input
                                id="end-date"
                                v-model="form.end_date"
                                type="date"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="event-type">Tipo de evento</Label>
                            <Input id="event-type" v-model="form.event_type" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="guests">Invitados</Label>
                            <Input
                                id="guests"
                                v-model="form.guests"
                                type="number"
                                min="1"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="subtotal">Total (MXN)</Label>
                            <Input
                                id="subtotal"
                                v-model="form.subtotal"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="deposit">Anticipo (MXN)</Label>
                            <Input
                                id="deposit"
                                v-model="form.deposit"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="expires-at">Vence</Label>
                            <Input
                                id="expires-at"
                                v-model="form.expires_at"
                                type="datetime-local"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="notes">Notas para el cliente</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="terms">Términos</Label>
                            <textarea
                                id="terms"
                                v-model="form.terms"
                                rows="3"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                Guardar cambios
                            </Button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Estado</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Comparte el link público; el cliente puede aceptar o
                        rechazar la cotización.
                    </p>
                    <div class="mt-4 flex flex-col gap-2">
                        <Button v-if="quote.can_send" @click="sendQuote">
                            Enviar por correo
                        </Button>
                        <Button v-if="quote.can_convert" @click="convert">
                            Convertir en reserva
                        </Button>
                        <Button variant="outline" as-child>
                            <a
                                :href="quote.public_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir link público
                            </a>
                        </Button>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
