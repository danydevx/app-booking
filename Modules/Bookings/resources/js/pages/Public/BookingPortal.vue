<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
        whatsapp_phone: string | null;
    };
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    event_type: string | null;
    guests: number | null;
    message: string | null;
    customer_confirmed_at: string | null;
    feedback: {
        rating: number | null;
        comment: string | null;
        at: string | null;
    };
    can_confirm: boolean;
    can_feedback: boolean;
    urls: {
        confirm: string;
        feedback: string;
        calendar: string;
    };
};

const props = defineProps<{
    booking: PortalBooking;
}>();

const confirmForm = useForm({
    name: props.booking.customer.name,
    phone: props.booking.customer.phone,
    whatsapp_phone: props.booking.customer.whatsapp_phone ?? '',
    guests: props.booking.guests ?? '',
});

const feedbackForm = useForm({
    rating: '',
    comment: '',
});

const confirmBookingError = computed<string | null>(() => {
    const errors = confirmForm.errors as Record<string, string | undefined>;

    return errors.booking ?? null;
});

const feedbackError = computed<string | null>(() => {
    const errors = feedbackForm.errors as Record<string, string | undefined>;

    return errors.feedback ?? null;
});

const statusVariant = computed<
    'default' | 'secondary' | 'destructive' | 'outline'
>(() => {
    if (props.booking.status === 'accepted') {
        return 'default';
    }

    if (
        props.booking.status === 'declined' ||
        props.booking.status === 'cancelled' ||
        props.booking.status === 'expired'
    ) {
        return 'destructive';
    }

    if (props.booking.status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
});

const confirmedLabel = computed(() => {
    if (!props.booking.customer_confirmed_at) {
        return null;
    }

    return new Date(props.booking.customer_confirmed_at).toLocaleString(
        'es-MX',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    );
});

function confirm(): void {
    confirmForm.post(props.booking.urls.confirm, { preserveScroll: true });
}

function sendFeedback(): void {
    feedbackForm.post(props.booking.urls.feedback, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Reserva · ${booking.listing.name}`" />

    <PublicShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-muted-foreground">Tu reserva en</p>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ booking.listing.name }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{
                            booking.listing.location ||
                            'Ubicación por confirmar'
                        }}
                    </p>
                </div>
                <Badge :variant="statusVariant">
                    {{ booking.status_label }}
                </Badge>
            </div>

            <div
                v-if="confirmedLabel"
                class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                Confirmaste tus datos el {{ confirmedLabel }}.
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Fechas</p>
                        <p class="font-medium">
                            {{ booking.start_date_label }} al
                            {{ booking.end_date_label }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Invitados</p>
                        <p class="font-medium">{{ booking.guests ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Evento</p>
                        <p class="font-medium">
                            {{ booking.event_type ?? 'No especificado' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Contacto</p>
                        <p class="font-medium">{{ booking.customer.email }}</p>
                    </div>
                </div>

                <p
                    v-if="booking.message"
                    class="mt-4 border-t pt-4 text-sm whitespace-pre-line text-muted-foreground"
                >
                    {{ booking.message }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2 border-t pt-4">
                    <Button variant="outline" as-child>
                        <a :href="booking.urls.calendar">
                            Agregar al calendario
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="booking.listing.url"> Ver terraza </Link>
                    </Button>
                </div>
            </div>

            <section
                v-if="booking.can_confirm"
                class="rounded-xl border bg-card p-5"
            >
                <h2 class="text-lg font-semibold">Confirmar mis datos</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Revisa y actualiza tus datos para que el anfitrión tenga la
                    información correcta.
                </p>
                <form
                    class="mt-4 grid gap-3 sm:grid-cols-2"
                    @submit.prevent="confirm"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="portal-name">Nombre</Label>
                        <Input
                            id="portal-name"
                            v-model="confirmForm.name"
                            maxlength="120"
                            required
                        />
                        <p
                            v-if="confirmForm.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ confirmForm.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="portal-phone">Teléfono</Label>
                        <Input
                            id="portal-phone"
                            v-model="confirmForm.phone"
                            maxlength="40"
                            required
                        />
                        <p
                            v-if="confirmForm.errors.phone"
                            class="text-sm text-destructive"
                        >
                            {{ confirmForm.errors.phone }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="portal-whatsapp">WhatsApp</Label>
                        <Input
                            id="portal-whatsapp"
                            v-model="confirmForm.whatsapp_phone"
                            maxlength="40"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="portal-guests">Invitados</Label>
                        <Input
                            id="portal-guests"
                            v-model.number="confirmForm.guests"
                            type="number"
                            min="1"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <p
                            v-if="confirmBookingError"
                            class="mb-2 text-sm text-destructive"
                        >
                            {{ confirmBookingError }}
                        </p>
                        <Button
                            type="submit"
                            :disabled="confirmForm.processing"
                        >
                            Confirmar datos
                        </Button>
                    </div>
                </form>
            </section>

            <section
                v-if="booking.can_feedback"
                class="rounded-xl border bg-card p-5"
            >
                <h2 class="text-lg font-semibold">¿Cómo estuvo tu evento?</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Tu opinión ayuda al anfitrión a mejorar.
                </p>
                <form class="mt-4 grid gap-3" @submit.prevent="sendFeedback">
                    <div class="grid gap-2">
                        <Label for="portal-rating">Calificación</Label>
                        <select
                            id="portal-rating"
                            v-model="feedbackForm.rating"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            required
                        >
                            <option value="">Selecciona</option>
                            <option value="5">5 · Excelente</option>
                            <option value="4">4 · Muy bien</option>
                            <option value="3">3 · Bien</option>
                            <option value="2">2 · Regular</option>
                            <option value="1">1 · Mal</option>
                        </select>
                        <p
                            v-if="feedbackForm.errors.rating"
                            class="text-sm text-destructive"
                        >
                            {{ feedbackForm.errors.rating }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="portal-comment">Comentario</Label>
                        <textarea
                            id="portal-comment"
                            v-model="feedbackForm.comment"
                            rows="3"
                            maxlength="1000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <p
                            v-if="feedbackForm.errors.comment"
                            class="text-sm text-destructive"
                        >
                            {{ feedbackForm.errors.comment }}
                        </p>
                    </div>
                    <div>
                        <p
                            v-if="feedbackError"
                            class="mb-2 text-sm text-destructive"
                        >
                            {{ feedbackError }}
                        </p>
                        <Button
                            type="submit"
                            :disabled="feedbackForm.processing"
                        >
                            Enviar opinión
                        </Button>
                    </div>
                </form>
            </section>

            <section
                v-if="booking.feedback.rating"
                class="rounded-xl border bg-card p-5"
            >
                <h2 class="text-lg font-semibold">Tu opinión</h2>
                <p class="mt-2 text-2xl font-semibold">
                    {{ booking.feedback.rating }}/5
                </p>
                <p
                    v-if="booking.feedback.comment"
                    class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                >
                    {{ booking.feedback.comment }}
                </p>
            </section>
        </div>
    </PublicShell>
</template>
