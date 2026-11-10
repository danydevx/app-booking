<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type PublicQuote = {
    id: number;
    status: string;
    status_label: string;
    customer_name: string;
    listing_name: string;
    listing_location: string;
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    event_type: string | null;
    guests: number | null;
    subtotal_formatted: string;
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
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ quote.listing_name }}
                    </h1>
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
                            {{ quote.subtotal_formatted }} {{ quote.currency }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Anticipo</p>
                        <p class="text-xl font-semibold">
                            {{ quote.deposit_formatted }} {{ quote.currency }}
                        </p>
                    </div>
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
