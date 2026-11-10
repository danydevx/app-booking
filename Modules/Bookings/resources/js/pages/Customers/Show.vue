<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    CustomerActivity,
    CustomerDetail,
    InquiryStatus,
    StatusOption,
} from '../../types';

const props = defineProps<{
    customer: CustomerDetail;
    activities: CustomerActivity[];
    activityTypes: StatusOption[];
    urls: {
        index: string;
        update: string;
        activities_store: string;
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
            {
                title: 'Ficha',
            },
        ],
    },
});

const form = useForm({
    name: props.customer.name,
    email: props.customer.email,
    phone: props.customer.phone ?? '',
    whatsapp_phone: props.customer.whatsapp_phone ?? '',
    notes: props.customer.notes ?? '',
});

const activityForm = useForm({
    type: 'note',
    body: '',
    occurred_at: '',
});

function submit(): void {
    form.patch(props.urls.update, { preserveScroll: true });
}

function recordActivity(): void {
    activityForm.post(props.urls.activities_store, {
        preserveScroll: true,
        onSuccess: () => {
            activityForm.reset('body', 'occurred_at');
            activityForm.type = 'note';
        },
    });
}

function deleteActivity(activity: CustomerActivity): void {
    if (!window.confirm('¿Eliminar esta actividad del historial?')) {
        return;
    }

    router.delete(activity.delete_url, { preserveScroll: true });
}

function formatActivityDate(value: string): string {
    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
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
</script>

<template>
    <Head :title="customer.name" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ customer.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ customer.email }} ·
                    {{ customer.phone ?? 'Sin teléfono' }}
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.index">Volver</Link>
            </Button>
        </div>

        <form class="rounded-xl border bg-card p-5" @submit.prevent="submit">
            <h2 class="mb-4 text-lg font-semibold">Datos del cliente</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" required />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Correo</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="phone">Teléfono</Label>
                    <Input id="phone" v-model="form.phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="whatsapp">WhatsApp</Label>
                    <Input id="whatsapp" v-model="form.whatsapp_phone" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="notes">Notas internas</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                </div>
            </div>
            <Button class="mt-4" type="submit" :disabled="form.processing">
                Guardar cliente
            </Button>
        </form>

        <section class="rounded-xl border bg-card p-5">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">Timeline</h2>
                <p class="text-sm text-muted-foreground">
                    Registra llamadas, correos, reuniones y notas de
                    seguimiento.
                </p>
            </div>

            <form
                class="grid gap-3 rounded-lg border bg-muted/20 p-4 sm:grid-cols-[10rem_1fr]"
                @submit.prevent="recordActivity"
            >
                <div class="grid gap-2">
                    <Label for="activity-type">Tipo</Label>
                    <select
                        id="activity-type"
                        v-model="activityForm.type"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option
                            v-for="type in activityTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <p
                        v-if="activityForm.errors.type"
                        class="text-sm text-destructive"
                    >
                        {{ activityForm.errors.type }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="activity-date">Fecha y hora opcional</Label>
                    <Input
                        id="activity-date"
                        v-model="activityForm.occurred_at"
                        type="datetime-local"
                    />
                    <p
                        v-if="activityForm.errors.occurred_at"
                        class="text-sm text-destructive"
                    >
                        {{ activityForm.errors.occurred_at }}
                    </p>
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="activity-body">Detalle</Label>
                    <textarea
                        id="activity-body"
                        v-model="activityForm.body"
                        rows="3"
                        maxlength="5000"
                        required
                        class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder="Ej. Llamé al cliente para confirmar el número de invitados."
                    />
                    <p
                        v-if="activityForm.errors.body"
                        class="text-sm text-destructive"
                    >
                        {{ activityForm.errors.body }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="activityForm.processing">
                        Registrar actividad
                    </Button>
                </div>
            </form>

            <div v-if="activities.length > 0" class="mt-5 space-y-3">
                <article
                    v-for="activity in activities"
                    :key="activity.id"
                    class="rounded-lg border p-3"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">
                                {{ activity.type_label }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ formatActivityDate(activity.occurred_at) }}
                            </span>
                        </div>
                        <Button
                            v-if="activity.can_delete"
                            size="sm"
                            variant="ghost"
                            @click="deleteActivity(activity)"
                        >
                            Eliminar
                        </Button>
                    </div>
                    <p class="mt-2 text-sm whitespace-pre-line">
                        {{ activity.body }}
                    </p>
                    <p
                        v-if="activity.author"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{ activity.author }}
                    </p>
                </article>
            </div>
            <p v-else class="mt-4 text-sm text-muted-foreground">
                Todavía no hay actividades registradas.
            </p>
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 text-lg font-semibold">Solicitudes</h2>
            <div v-if="customer.inquiries.length > 0" class="space-y-3">
                <article
                    v-for="inquiry in customer.inquiries"
                    :key="inquiry.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
                >
                    <div>
                        <p class="font-medium">{{ inquiry.listing.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ inquiry.start_date_label }} al
                            {{ inquiry.end_date_label }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge :variant="statusVariant(inquiry.status)">
                            {{ inquiry.status_label }}
                        </Badge>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="inquiry.show_url">Ver</Link>
                        </Button>
                    </div>
                </article>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Este cliente no tiene solicitudes.
            </p>
        </section>
    </div>
</template>
