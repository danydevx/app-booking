<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type PublicQuote = {
    id: number;
    folio: string | null;
    status: string;
    status_label: string;
    customer_name: string;
    listing_name: string;
    listing_logo_url: string | null;
    listing_location: string;
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    event_type: string | null;
    guests: number | null;
    items: Array<{
        description: string;
        details: string | null;
        quantity: number;
        unit: string;
        unit_price_formatted: string;
        amount_formatted: string;
    }>;
    tax_mode: 'none' | 'added' | 'included';
    tax_rate_bps: number;
    subtotal_formatted: string;
    discount_formatted: string;
    tax_formatted: string;
    total_formatted: string;
    deposit_formatted: string;
    currency: string;
    notes: string | null;
    terms: string | null;
    expires_at: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    can_respond: boolean;
    accept_url: string;
    decline_url: string;
};

const props = defineProps<{
    quote: PublicQuote;
}>();

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

function accept(): void {
    if (!window.confirm('¿Aceptar esta cotización?')) {
        return;
    }

    router.post(props.quote.accept_url, {}, { preserveScroll: true });
}

function decline(): void {
    if (!window.confirm('¿Rechazar esta cotización?')) {
        return;
    }

    router.post(props.quote.decline_url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Cotización · ${quote.listing_name}`" />

    <PublicShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-muted-foreground">Cotización para</p>
                    <div class="flex items-center gap-3">
                        <img
                            v-if="quote.listing_logo_url"
                            :src="quote.listing_logo_url"
                            :alt="`Logotipo de ${quote.listing_name}`"
                            class="h-12 w-12 rounded-md border object-contain p-1"
                        />
                        <h1 class="text-3xl font-semibold tracking-tight">
                            {{ quote.listing_name }}
                        </h1>
                    </div>
                    <p v-if="quote.folio" class="text-sm text-muted-foreground">
                        Cotización {{ quote.folio }}
                    </p>
                    <p class="text-muted-foreground">
                        {{
                            quote.listing_location || 'Ubicación por confirmar'
                        }}
                    </p>
                </div>
                <Badge :variant="statusVariant">
                    {{ quote.status_label }}
                </Badge>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Cliente</p>
                        <p class="font-medium">{{ quote.customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Fechas</p>
                        <p class="font-medium">
                            {{ quote.start_date_label }} al
                            {{ quote.end_date_label }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Evento</p>
                        <p class="font-medium">
                            {{ quote.event_type ?? 'No especificado' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Invitados</p>
                        <p class="font-medium">{{ quote.guests ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total</p>
                        <p class="text-xl font-semibold">
                            {{ quote.total_formatted }} {{ quote.currency }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Anticipo</p>
                        <p class="text-xl font-semibold">
                            {{ quote.deposit_formatted }} {{ quote.currency }}
                        </p>
                    </div>
                </div>

                <div v-if="quote.items.length" class="mt-5 overflow-x-auto">
                    <h2 class="text-sm font-medium">Conceptos</h2>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs text-muted-foreground"
                            >
                                <th class="py-2 pr-3">Concepto</th>
                                <th class="py-2 pr-3">Cantidad</th>
                                <th class="py-2 text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in quote.items"
                                :key="index"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-3">
                                    <p class="font-medium">
                                        {{ item.description }}
                                    </p>
                                    <p
                                        v-if="item.details"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ item.details }}
                                    </p>
                                </td>
                                <td class="py-2 pr-3">
                                    {{ item.quantity }} {{ item.unit }}
                                </td>
                                <td class="py-2 text-right">
                                    {{ item.amount_formatted }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <dl class="mt-3 ml-auto max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ quote.subtotal_formatted }}</dd>
                        </div>
                        <div
                            v-if="quote.discount_formatted !== '$0.00'"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Descuento</dt>
                            <dd>-{{ quote.discount_formatted }}</dd>
                        </div>
                        <div
                            v-if="quote.tax_mode !== 'none'"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">
                                IVA {{ (quote.tax_rate_bps / 100).toFixed(2) }}%
                                {{
                                    quote.tax_mode === 'included'
                                        ? '(incluido)'
                                        : ''
                                }}
                            </dt>
                            <dd>{{ quote.tax_formatted }}</dd>
                        </div>
                        <div
                            class="flex justify-between gap-4 border-t pt-1 font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>{{ quote.total_formatted }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="quote.notes" class="mt-5">
                    <h2 class="text-sm font-medium">Notas</h2>
                    <p
                        class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ quote.notes }}
                    </p>
                </div>
                <div v-if="quote.terms" class="mt-5">
                    <h2 class="text-sm font-medium">Términos</h2>
                    <p
                        class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ quote.terms }}
                    </p>
                </div>
            </div>

            <div v-if="quote.can_respond" class="flex flex-wrap gap-3">
                <Button @click="accept">Aceptar cotización</Button>
                <Button variant="outline" @click="decline">
                    Rechazar cotización
                </Button>
            </div>
            <div
                v-else
                class="rounded-xl border bg-muted/40 p-4 text-sm text-muted-foreground"
            >
                <p v-if="quote.status === 'accepted'">
                    Esta cotización fue aceptada. El propietario se pondrá en
                    contacto contigo.
                </p>
                <p v-else-if="quote.status === 'declined'">
                    Esta cotización fue rechazada.
                </p>
                <p v-else-if="quote.status === 'expired'">
                    Esta cotización venció. Solicita una nueva al propietario.
                </p>
                <p v-else>
                    Esta cotización ya no está disponible para responder.
                </p>
            </div>
        </div>
    </PublicShell>
</template>
