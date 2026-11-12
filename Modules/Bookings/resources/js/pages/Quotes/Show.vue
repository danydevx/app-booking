<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { CustomerQuoteCard, StatusOption } from '../../types';

const props = defineProps<{
    quote: CustomerQuoteCard;
    statuses: StatusOption[];
    urls: {
        index: string;
        bookings: string;
        customer: string;
        booking: string | null;
    };
}>();

defineOptions({
    layout: (props: { urls: { index: string; bookings: string } }) => ({
        breadcrumbs: [
            { title: 'Solicitudes', href: props.urls.bookings },
            { title: 'Cotizaciones', href: props.urls.index },
            { title: 'Detalle' },
        ],
    }),
});

const statusVariant = computed<
    'default' | 'secondary' | 'destructive' | 'outline'
>(() => {
    if (props.quote.status === 'accepted') {
        return 'default';
    }

    if (
        props.quote.status === 'declined' ||
        props.quote.status === 'expired' ||
        props.quote.status === 'cancelled'
    ) {
        return 'destructive';
    }

    if (props.quote.status === 'sent') {
        return 'secondary';
    }

    return 'outline';
});

const rentalModeLabel = computed(() => {
    return {
        hour: 'Renta por hora',
        day: 'Renta por día',
        fixed: 'Precio fijo por evento',
    }[props.quote.rental_mode];
});

const taxLabel = computed(() => {
    const rate = (props.quote.tax_rate_bps / 100).toFixed(2);

    return {
        none: 'Sin IVA desglosado',
        added: `IVA ${rate}% agregado`,
        included: `IVA ${rate}% incluido`,
    }[props.quote.tax_mode];
});

function sendQuote(): void {
    if (
        !window.confirm(
            '¿Enviar la cotización al correo del cliente? Se compartirá el enlace público.',
        )
    ) {
        return;
    }

    router.post(props.quote.send_url, {}, { preserveScroll: true });
}

function duplicate(): void {
    router.post(props.quote.duplicate_url);
}

function cancelQuote(): void {
    if (
        !window.confirm(
            '¿Cancelar esta cotización? El PDF emitido se conserva como registro histórico.',
        )
    ) {
        return;
    }

    router.post(props.quote.cancel_url);
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
    <Head :title="`Cotización ${quote.folio}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">
                        Cotización {{ quote.folio }}
                    </h1>
                    <Badge :variant="statusVariant">
                        {{ quote.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ quote.customer.name }} · {{ quote.listing.name }} ·
                    {{ quote.start_date_label
                    }}<span
                        v-if="quote.end_date_label !== quote.start_date_label"
                    >
                        al {{ quote.end_date_label }}</span
                    >
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="quote.can_edit" as-child>
                    <Link :href="quote.edit_url">Editar borrador</Link>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="quote.preview_url" target="_blank" rel="noopener">
                        Vista previa
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="quote.pdf_url" target="_blank" rel="noopener">
                        Descargar PDF
                    </a>
                </Button>
                <Button variant="outline" @click="duplicate">Duplicar</Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.index">Volver</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Total</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.total_formatted }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Subtotal</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.subtotal_formatted }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">IVA</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ quote.tax_formatted }}
                </p>
                <p class="text-xs text-muted-foreground">{{ taxLabel }}</p>
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
                    <h2 class="text-lg font-semibold">Datos comerciales</h2>
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
                            <dt class="text-muted-foreground">Teléfono</dt>
                            <dd>{{ quote.customer.phone ?? '—' }}</dd>
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
                            <dt class="text-muted-foreground">Invitados</dt>
                            <dd>{{ quote.guests ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Modalidad</dt>
                            <dd>
                                {{ rentalModeLabel }}
                                <span v-if="quote.billing_quantity">
                                    · {{ quote.billing_quantity }}
                                    {{
                                        quote.rental_mode === 'hour'
                                            ? 'hora(s)'
                                            : quote.rental_mode === 'day'
                                              ? 'día(s)'
                                              : 'evento'
                                    }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Horario</dt>
                            <dd>
                                {{
                                    quote.start_time
                                        ? `${quote.start_time} - ${quote.end_time ?? 'Por definir'}`
                                        : 'No aplica'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Emitida</dt>
                            <dd>
                                {{
                                    quote.issued_at
                                        ? new Date(
                                              quote.issued_at,
                                          ).toLocaleString('es-MX', {
                                              dateStyle: 'medium',
                                              timeStyle: 'short',
                                          })
                                        : 'Borrador'
                                }}
                            </dd>
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
                    </dl>

                    <div v-if="urls.booking" class="mt-4">
                        <Button variant="outline" as-child>
                            <Link :href="urls.booking">Ver reserva</Link>
                        </Button>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Conceptos</h2>
                    <div
                        v-if="quote.items.length === 0"
                        class="mt-3 text-sm text-muted-foreground"
                    >
                        Esta cotización no tiene conceptos detallados.
                    </div>
                    <div v-else class="mt-3 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-xs text-muted-foreground"
                                >
                                    <th class="py-2 pr-3">Concepto</th>
                                    <th class="py-2 pr-3">Cantidad</th>
                                    <th class="py-2 pr-3 text-right">
                                        Precio unitario
                                    </th>
                                    <th class="py-2 text-right">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in quote.items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-3">
                                        <p class="font-medium">
                                            {{ item.description }}
                                        </p>
                                        <p
                                            v-if="item.details"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.details }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.type_label }}
                                        </p>
                                    </td>
                                    <td class="py-3 pr-3">
                                        {{ item.quantity }}
                                        {{ item.unit_label }}
                                    </td>
                                    <td class="py-3 pr-3 text-right">
                                        {{ item.unit_price_formatted }}
                                    </td>
                                    <td class="py-3 text-right font-medium">
                                        {{ item.amount_formatted }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <dl class="mt-4 ml-auto max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ quote.subtotal_formatted }}</dd>
                        </div>
                        <div
                            v-if="quote.discount_cents > 0"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Descuento</dt>
                            <dd>-{{ quote.discount_formatted }}</dd>
                        </div>
                        <div
                            v-if="quote.discount_cents > 0"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Base para IVA</dt>
                            <dd>{{ quote.tax_base_formatted }}</dd>
                        </div>
                        <div
                            v-if="quote.tax_mode !== 'none'"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">
                                {{ taxLabel }}
                            </dt>
                            <dd>{{ quote.tax_formatted }}</dd>
                        </div>
                        <div
                            class="flex justify-between gap-4 border-t pt-2 text-base font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>{{ quote.total_formatted }}</dd>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="quote.notes || quote.terms"
                    class="rounded-xl border bg-card p-5"
                >
                    <div v-if="quote.terms">
                        <h2 class="text-lg font-semibold">
                            Condiciones comerciales
                        </h2>
                        <p
                            class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ quote.terms }}
                        </p>
                    </div>
                    <div v-if="quote.notes" :class="quote.terms ? 'mt-5' : ''">
                        <h2 class="text-lg font-semibold">Observaciones</h2>
                        <p
                            class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ quote.notes }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="quote.history.length"
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="text-lg font-semibold">Historial</h2>
                    <ol class="mt-3 space-y-3 text-sm">
                        <li
                            v-for="entry in quote.history"
                            :key="entry.id"
                            class="border-l-2 border-muted pl-3"
                        >
                            <p class="font-medium">
                                {{ entry.to_status_label ?? entry.to_status }}
                            </p>
                            <p v-if="entry.note" class="text-muted-foreground">
                                {{ entry.note }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ entry.changed_by ?? 'Sistema' }}
                                <span v-if="entry.created_at">
                                    ·
                                    {{
                                        new Date(
                                            entry.created_at,
                                        ).toLocaleString('es-MX')
                                    }}
                                </span>
                            </p>
                        </li>
                    </ol>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Acciones</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Comparte el link público; el cliente puede aceptar o
                        rechazar la cotización.
                    </p>
                    <div class="mt-4 flex flex-col gap-2">
                        <Button
                            v-if="quote.can_issue"
                            @click="router.post(quote.issue_url)"
                        >
                            Emitir cotización
                        </Button>
                        <Button v-if="quote.can_send" @click="sendQuote">
                            Enviar por correo
                        </Button>
                        <Button v-if="quote.can_convert" @click="convert">
                            Convertir en reserva
                        </Button>
                        <Button
                            v-if="quote.can_cancel"
                            variant="destructive"
                            @click="cancelQuote"
                        >
                            Cancelar cotización
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

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Emisor</h2>
                    <div class="mt-3 flex items-center gap-3">
                        <img
                            v-if="quote.listing.logo_url"
                            :src="quote.listing.logo_url"
                            :alt="`Logotipo de ${quote.listing.name}`"
                            class="h-12 w-12 rounded-md border object-contain p-1"
                        />
                        <div>
                            <p class="font-medium">{{ quote.listing.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    quote.listing.contact_email ??
                                    'Sin correo comercial'
                                }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    quote.listing.contact_phone ??
                                    'Sin teléfono comercial'
                                }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Documento de cotización comercial. No constituye factura
                        fiscal ni comprobante CFDI.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
