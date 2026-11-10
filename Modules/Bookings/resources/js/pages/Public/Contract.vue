<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type PortalBooking = {
    id: number;
    status: string;
    status_label: string;
    listing: {
        name: string;
        location: string;
        url: string;
    };
    customer: {
        name: string;
        email: string;
        phone: string;
    };
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    event_type: string | null;
    guests: number | null;
    urls: {
        portal: string;
        confirm: string;
        feedback: string;
        calendar: string;
    };
};

type Contract = {
    id: number;
    title: string;
    body: string;
    status: string;
    status_label: string;
    sent_at: string | null;
    accepted_at: string | null;
    accepted_by_name: string | null;
    can_accept: boolean;
    url: string;
    accept_url: string;
};

const props = defineProps<{
    booking: PortalBooking;
    contract: Contract;
}>();

function printContract(): void {
    window.print();
}
</script>

<template>
    <Head :title="contract.title" />

    <PublicShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <div
                class="flex flex-wrap items-start justify-between gap-3 print:hidden"
            >
                <div>
                    <p class="text-sm text-muted-foreground">
                        Contrato de reserva
                    </p>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ contract.title }}
                    </h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="printContract">
                        Imprimir / Guardar PDF
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="booking.urls.portal">
                            Volver al portal
                        </Link>
                    </Button>
                </div>
            </div>

            <article class="rounded-xl border bg-card p-6 sm:p-8">
                <header class="border-b pb-4">
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-xl font-semibold">
                                {{ booking.listing.name }}
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ booking.listing.location }}
                            </p>
                        </div>
                        <Badge variant="outline">
                            {{ contract.status_label }}
                        </Badge>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Cliente</dt>
                            <dd class="font-medium">
                                {{ booking.customer.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Contacto</dt>
                            <dd>
                                {{ booking.customer.email }} ·
                                {{ booking.customer.phone }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Fechas</dt>
                            <dd class="font-medium">
                                {{ booking.start_date_label }} al
                                {{ booking.end_date_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Evento</dt>
                            <dd>
                                {{ booking.event_type ?? 'No especificado' }}
                                <span v-if="booking.guests">
                                    · {{ booking.guests }} invitados
                                </span>
                            </dd>
                        </div>
                    </dl>
                </header>

                <div class="mt-6 text-sm leading-6 whitespace-pre-line">
                    {{ contract.body }}
                </div>

                <footer class="mt-8 border-t pt-4 text-sm">
                    <template v-if="contract.accepted_at">
                        <p class="font-medium">Aceptado</p>
                        <p class="text-muted-foreground">
                            {{ contract.accepted_by_name }} ·
                            {{
                                new Date(contract.accepted_at).toLocaleString(
                                    'es-MX',
                                    {
                                        dateStyle: 'medium',
                                        timeStyle: 'short',
                                    },
                                )
                            }}
                        </p>
                    </template>
                    <p v-else class="text-muted-foreground">
                        Pendiente de aceptación por el cliente.
                    </p>
                </footer>
            </article>
        </div>
    </PublicShell>
</template>
