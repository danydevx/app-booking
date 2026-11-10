<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DepositForm from '../components/DepositForm.vue';
import type {
    BookingActivity,
    BookingDeposit,
    InquiryDetail,
    InquiryStatus,
    StatusOption,
} from '../types';

const props = defineProps<{
    inquiry: InquiryDetail;
    activities: BookingActivity[];
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
        contract_store: string;
    };
}>();

defineOptions({
    layout: (props: { urls: { index: string } }) => ({
        breadcrumbs: [
            {
                title: 'Solicitudes',
                href: props.urls.index,
            },
            {
                title: 'Detalle',
            },
        ],
    }),
});

const contractForm = useForm({
    title: props.inquiry.contract?.title ?? 'Contrato de renta',
    body: props.inquiry.contract?.body ?? '',
});

const contractFormError = computed<string | null>(() => {
    const errors = contractForm.errors as Record<string, string | undefined>;

    return errors.contract ?? null;
});

function saveContract(): void {
    if (props.inquiry.contract) {
        contractForm.patch(props.inquiry.contract.update_url, {
            preserveScroll: true,
        });

        return;
    }

    contractForm.post(props.urls.contract_store, { preserveScroll: true });
}

function publishContract(): void {
    if (!props.inquiry.contract) {
        return;
    }

    if (!window.confirm('¿Publicar el contrato en el portal del cliente?')) {
        return;
    }

    router.post(
        props.inquiry.contract.publish_url,
        {},
        {
            preserveScroll: true,
        },
    );
}

function contractStatusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (status === 'sent') {
        return 'secondary';
    }

    return 'outline';
}

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

function formatActivityDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head :title="`Solicitud de ${inquiry.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
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
                    <a
                        :href="inquiry.portal_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Portal del cliente
                    </a>
                </Button>
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
                            <dd class="font-medium">
                                {{ inquiry.source_detail ?? inquiry.source }}
                            </dd>
                        </div>
                        <div v-if="inquiry.utm_source || inquiry.utm_campaign">
                            <dt class="text-muted-foreground">Campaña</dt>
                            <dd class="font-medium">
                                {{ inquiry.utm_source ?? '—' }}
                                <span v-if="inquiry.utm_campaign">
                                    · {{ inquiry.utm_campaign }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="inquiry.referrer" class="sm:col-span-2">
                            <dt class="text-muted-foreground">Referencia</dt>
                            <dd class="truncate font-medium">
                                <a
                                    :href="inquiry.referrer"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="underline underline-offset-4"
                                >
                                    {{ inquiry.referrer }}
                                </a>
                            </dd>
                        </div>
                    </dl>
                    <p
                        v-if="inquiry.message"
                        class="mt-4 border-t pt-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ inquiry.message }}
                    </p>
                </section>

                <section
                    v-if="
                        inquiry.customer_confirmed_at || inquiry.feedback_rating
                    "
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="mb-4 text-lg font-semibold">
                        Confirmación del cliente
                    </h2>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div v-if="inquiry.customer_confirmed_at">
                            <dt class="text-muted-foreground">
                                Datos confirmados
                            </dt>
                            <dd class="font-medium">
                                {{
                                    formatActivityDate(
                                        inquiry.customer_confirmed_at,
                                    )
                                }}
                            </dd>
                        </div>
                        <div v-if="inquiry.feedback_rating">
                            <dt class="text-muted-foreground">Opinión</dt>
                            <dd class="font-medium">
                                {{ inquiry.feedback_rating }}/5
                                <span v-if="inquiry.feedback_at">
                                    ·
                                    {{
                                        formatActivityDate(inquiry.feedback_at)
                                    }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                    <p
                        v-if="inquiry.feedback_comment"
                        class="mt-3 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ inquiry.feedback_comment }}
                    </p>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="mb-4 flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Contrato</h2>
                            <p class="text-sm text-muted-foreground">
                                Términos que el cliente puede aceptar desde su
                                portal.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge
                                v-if="inquiry.contract"
                                :variant="
                                    contractStatusVariant(
                                        inquiry.contract.status,
                                    )
                                "
                            >
                                {{ inquiry.contract.status_label }}
                            </Badge>
                            <Button
                                v-if="inquiry.contract"
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <a
                                    :href="inquiry.contract.public_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Ver contrato
                                </a>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="
                            inquiry.contract &&
                            inquiry.contract.status === 'accepted'
                        "
                        class="space-y-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Aceptado por
                            <strong>{{
                                inquiry.contract.accepted_by_name
                            }}</strong>
                            <span v-if="inquiry.contract.accepted_at">
                                el
                                {{
                                    formatActivityDate(
                                        inquiry.contract.accepted_at,
                                    )
                                }}
                            </span>
                        </p>
                        <div
                            class="rounded-lg border bg-muted/20 p-4 text-sm whitespace-pre-line"
                        >
                            {{ inquiry.contract.body }}
                        </div>
                    </div>

                    <form
                        v-else
                        class="grid gap-3"
                        @submit.prevent="saveContract"
                    >
                        <div class="grid gap-2">
                            <Label for="contract-title">Título</Label>
                            <Input
                                id="contract-title"
                                v-model="contractForm.title"
                                maxlength="120"
                                required
                            />
                            <p
                                v-if="contractForm.errors.title"
                                class="text-sm text-destructive"
                            >
                                {{ contractForm.errors.title }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="contract-body">Términos</Label>
                            <textarea
                                id="contract-body"
                                v-model="contractForm.body"
                                rows="10"
                                maxlength="20000"
                                required
                                class="min-h-48 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Escribe las condiciones de la renta, anticipo, cancelación y reglas de la terraza."
                            />
                            <p
                                v-if="contractForm.errors.body"
                                class="text-sm text-destructive"
                            >
                                {{ contractForm.errors.body }}
                            </p>
                        </div>
                        <p
                            v-if="contractFormError"
                            class="text-sm text-destructive"
                        >
                            {{ contractFormError }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                :disabled="contractForm.processing"
                            >
                                {{
                                    inquiry.contract
                                        ? 'Guardar contrato'
                                        : 'Crear contrato'
                                }}
                            </Button>
                            <Button
                                v-if="inquiry.contract?.can_edit"
                                type="button"
                                variant="outline"
                                @click="publishContract"
                            >
                                {{
                                    inquiry.contract.status === 'sent'
                                        ? 'Actualizar en portal'
                                        : 'Publicar en portal'
                                }}
                            </Button>
                        </div>
                    </form>
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

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 text-lg font-semibold">Historial</h2>
                    <div v-if="activities.length > 0" class="divide-y">
                        <div
                            v-for="activity in activities"
                            :key="activity.id"
                            class="py-3 text-sm"
                        >
                            <p class="font-medium">{{ activity.label }}</p>
                            <p
                                v-if="activity.summary"
                                class="text-muted-foreground"
                            >
                                {{ activity.summary }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ formatActivityDate(activity.created_at) }}
                                <span v-if="activity.causer">
                                    · {{ activity.causer }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Todavía no hay cambios registrados.
                    </p>
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
