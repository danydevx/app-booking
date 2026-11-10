<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DepositForm from '../components/DepositForm.vue';
import type {
    BookingDeposit,
    InquiryDetail,
    InquiryStatus,
    StatusOption,
} from '../types';

const props = defineProps<{
    inquiry: InquiryDetail;
    statuses: StatusOption[];
    depositStatuses: StatusOption[];
    depositMethods: StatusOption[];
    canManage: boolean;
    canEdit: boolean;
    canManageDeposits: boolean;
    urls: {
        index: string;
        edit: string;
        update: string;
        contacted: string;
        accept: string;
        decline: string;
        cancel: string;
        deposits_store: string;
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
                title: 'Detalle',
            },
        ],
    },
});

function statusVariant(
    status: InquiryStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (
        status === 'declined' ||
        status === 'cancelled' ||
        status === 'expired'
    ) {
        return 'destructive';
    }

    if (status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
}

function markContacted(): void {
    router.patch(props.urls.contacted, {}, { preserveScroll: true });
}

function accept(): void {
    if (!window.confirm('¿Aceptar la solicitud y reservar estas fechas?')) {
        return;
    }

    router.patch(props.urls.accept, {}, { preserveScroll: true });
}

function decline(): void {
    if (!window.confirm('¿Rechazar esta solicitud?')) {
        return;
    }

    router.patch(props.urls.decline, {}, { preserveScroll: true });
}

function cancel(): void {
    if (!window.confirm('¿Cancelar la reserva y liberar las fechas?')) {
        return;
    }

    router.patch(props.urls.cancel, {}, { preserveScroll: true });
}

function updateDepositStatus(deposit: BookingDeposit, event: Event): void {
    const status = (event.target as HTMLSelectElement).value;

    router.patch(deposit.update_url, { status }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Solicitud de ${inquiry.name}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ inquiry.name }}</h1>
                    <Badge :variant="statusVariant(inquiry.status)">
                        {{ inquiry.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ inquiry.listing.name }} ·
                    {{ inquiry.start_date_label }} al
                    {{ inquiry.end_date_label }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.index">Volver</Link>
                </Button>
                <Button
                    v-if="
                        canEdit &&
                        !['declined', 'cancelled', 'expired'].includes(
                            inquiry.status,
                        )
                    "
                    variant="outline"
                    as-child
                >
                    <Link :href="urls.edit">Editar reserva</Link>
                </Button>
                <Button
                    v-if="inquiry.status === 'new'"
                    variant="outline"
                    @click="markContacted"
                >
                    Marcar contactada
                </Button>
                <Button
                    v-if="
                        canManage &&
                        ['new', 'contacted'].includes(inquiry.status)
                    "
                    @click="accept"
                >
                    Aceptar
                </Button>
                <Button
                    v-if="
                        canManage &&
                        !['declined', 'cancelled'].includes(inquiry.status)
                    "
                    variant="destructive"
                    @click="decline"
                >
                    Rechazar
                </Button>
                <Button
                    v-if="canManage && inquiry.status === 'accepted'"
                    variant="outline"
                    @click="cancel"
                >
                    Cancelar reserva
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 text-lg font-semibold">Solicitud</h2>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Fechas</dt>
                            <dd class="font-medium">
                                {{ inquiry.start_date_label }} al
                                {{ inquiry.end_date_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Invitados</dt>
                            <dd class="font-medium">
                                {{ inquiry.guests ?? 'No indicado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Evento</dt>
                            <dd class="font-medium">
                                {{ inquiry.event_type ?? 'No indicado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Origen</dt>
                            <dd class="font-medium">{{ inquiry.source }}</dd>
                        </div>
                    </dl>
                    <p
                        v-if="inquiry.message"
                        class="mt-4 border-t pt-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ inquiry.message }}
                    </p>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <h2 class="text-lg font-semibold">Anticipos</h2>
                        <span class="text-xs text-muted-foreground">
                            Registro manual, fuera de la plataforma
                        </span>
                    </div>

                    <div
                        v-if="inquiry.deposits.length > 0"
                        class="mt-4 space-y-3"
                    >
                        <article
                            v-for="deposit in inquiry.deposits"
                            :key="deposit.id"
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ deposit.amount_formatted }}
                                    {{ deposit.currency }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ deposit.method_label }}
                                    <span v-if="deposit.reference">
                                        · {{ deposit.reference }}
                                    </span>
                                    <span v-if="deposit.received_at">
                                        · {{ deposit.received_at }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary">{{
                                    deposit.status_label
                                }}</Badge>
                                <select
                                    v-if="canManageDeposits"
                                    :value="deposit.status"
                                    class="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                                    @change="
                                        updateDepositStatus(deposit, $event)
                                    "
                                >
                                    <option
                                        v-for="status in depositStatuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Todavía no hay anticipos registrados.
                    </p>

                    <div v-if="canManageDeposits" class="mt-5 border-t pt-5">
                        <DepositForm
                            :store-url="urls.deposits_store"
                            :statuses="depositStatuses"
                            :methods="depositMethods"
                        />
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-3 text-lg font-semibold">Contacto</h2>
                    <div class="space-y-2 text-sm">
                        <p class="font-medium">{{ inquiry.name }}</p>
                        <p>
                            <a
                                class="underline"
                                :href="`mailto:${inquiry.email}`"
                                >{{ inquiry.email }}</a
                            >
                        </p>
                        <p>
                            <a
                                class="underline"
                                :href="`tel:${inquiry.phone}`"
                                >{{ inquiry.phone }}</a
                            >
                        </p>
                        <p v-if="inquiry.whatsapp_phone">
                            {{ inquiry.whatsapp_phone }}
                        </p>
                    </div>
                    <div class="mt-4 flex flex-col gap-2">
                        <Button v-if="inquiry.whatsapp_url" as-child>
                            <a
                                :href="inquiry.whatsapp_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir WhatsApp
                            </a>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link
                                :href="inquiry.public_listing_url"
                                target="_blank"
                            >
                                Ver terraza pública
                            </Link>
                        </Button>
                    </div>
                </section>

                <section
                    v-if="inquiry.customer"
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="mb-3 text-lg font-semibold">Cliente</h2>
                    <p class="font-medium">{{ inquiry.customer.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ inquiry.customer.email }}
                    </p>
                    <Button class="mt-3" size="sm" variant="outline" as-child>
                        <Link :href="inquiry.customer.show_url">
                            Ver ficha
                        </Link>
                    </Button>
                </section>
            </aside>
        </div>
    </div>
</template>
