<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Loader2, Mail, Send } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { z } from 'zod';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFrontValidation } from '@/composables/useFrontValidation';

type GuestRange = {
    value: string;
    label: string;
};

type DateMode = 'exact' | 'approximate';

type ContactFormData = {
    name: string;
    email: string;
    phone: string;
    message: string;
    event_type: string;
    date_mode: DateMode;
    event_date: string;
    event_month: string;
    guest_range: string;
    website: string;
};

const props = defineProps<{
    open: boolean;
    listingName: string;
    storeUrl: string;
    guestRanges: GuestRange[];
    privacyUrl: string | null;
    defaultMessage: string;
    maxMessageLength: number;
    today: string;
    currentMonth: string;
    capacity: number | null;
    prefill?: {
        event_type?: string | null;
        guest_range?: string | null;
        event_date?: string | null;
        event_month?: string | null;
        message?: string | null;
    } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const sent = ref(false);
const submitting = ref(false);

const form = useForm<ContactFormData>({
    name: '',
    email: '',
    phone: '',
    message: props.defaultMessage,
    event_type: '',
    date_mode: 'exact',
    event_date: '',
    event_month: '',
    guest_range: '',
    website: '',
});

const schema = z
    .object({
        name: z
            .string()
            .trim()
            .min(1, 'El nombre es obligatorio.')
            .max(120, 'El nombre no puede superar 120 caracteres.'),
        email: z
            .string()
            .min(1, 'El correo es obligatorio.')
            .email('Indica un correo válido.')
            .max(255, 'El correo no puede superar 255 caracteres.'),
        phone: z
            .string()
            .max(40, 'El teléfono no puede superar 40 caracteres.')
            .refine((value) => {
                if (value.trim() === '') {
                    return true;
                }

                const digits = value.replace(/\D+/g, '');

                return digits.length >= 10 && digits.length <= 13;
            }, 'Ingresa un teléfono de 10 dígitos (puedes incluir +52).'),
        message: z
            .string()
            .trim()
            .min(1, 'El mensaje es obligatorio.')
            .max(
                props.maxMessageLength,
                `El mensaje no puede superar ${props.maxMessageLength} caracteres.`,
            ),
        event_type: z
            .string()
            .trim()
            .max(80, 'El tipo de evento no puede superar 80 caracteres.'),
        date_mode: z.enum(['exact', 'approximate']),
        event_date: z.string(),
        event_month: z.string(),
        guest_range: z.string().min(1, 'Selecciona el número de invitados.'),
        website: z.string().max(0, 'Campo no válido.'),
    })
    .superRefine((values, context) => {
        if (values.date_mode === 'exact' && values.event_date === '') {
            context.addIssue({
                code: 'custom',
                path: ['event_date'],
                message: 'Selecciona la fecha del evento.',
            });
        }

        if (values.date_mode === 'approximate' && values.event_month === '') {
            context.addIssue({
                code: 'custom',
                path: ['event_month'],
                message: 'Selecciona el mes aproximado del evento.',
            });
        }
    });

const { validate } = useFrontValidation(schema, () => ({
    name: form.name,
    email: form.email,
    phone: form.phone,
    message: form.message,
    event_type: form.event_type,
    date_mode: form.date_mode,
    event_date: form.event_date,
    event_month: form.event_month,
    guest_range: form.guest_range,
    website: form.website,
}));

const exceedsCapacity = computed(() => {
    if (
        props.capacity === null ||
        props.capacity <= 0 ||
        form.guest_range === ''
    ) {
        return false;
    }

    const minimum = form.guest_range.endsWith('+')
        ? 151
        : Number(form.guest_range.split('-')[0]);

    return Number.isFinite(minimum) && minimum > props.capacity;
});

function onOpenChange(value: boolean): void {
    emit('update:open', value);
}

function setDateMode(mode: DateMode): void {
    form.date_mode = mode;

    if (mode === 'exact') {
        form.event_month = '';
    } else {
        form.event_date = '';
    }
}

async function submit(): Promise<void> {
    if (submitting.value || form.processing) {
        return;
    }

    submitting.value = true;

    if (!(await validate(form))) {
        submitting.value = false;

        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            sent.value = true;
            form.reset();
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        sent.value = false;
        submitting.value = false;
        form.clearErrors();
        form.reset();

        form.message = props.prefill?.message ?? props.defaultMessage;
        form.event_type = props.prefill?.event_type ?? '';
        form.event_date = props.prefill?.event_date ?? '';
        form.event_month = props.prefill?.event_month ?? '';
        form.date_mode = props.prefill?.event_month ? 'approximate' : 'exact';
        form.guest_range = props.prefill?.guest_range ?? '';
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogScrollContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-lg p-0 sm:max-w-2xl"
        >
            <DialogHeader class="border-b p-6 pr-12 text-left">
                <p
                    class="flex items-center gap-1.5 text-xs font-bold tracking-[0.12em] text-muted-foreground uppercase"
                >
                    <Mail class="size-3.5" />
                    Ficha pública
                </p>
                <DialogTitle class="mt-2 text-2xl">
                    Contactar al propietario
                </DialogTitle>
                <DialogDescription class="mt-1">
                    Envía tu consulta sobre
                    <span class="font-semibold text-foreground">{{
                        listingName
                    }}</span>
                    y el propietario podrá responderte con los datos que
                    proporciones.
                </DialogDescription>
            </DialogHeader>

            <div v-if="sent" class="p-6" role="status" aria-live="polite">
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                    >
                        <CheckCircle2 class="size-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">
                            ¡Solicitud enviada!
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            El propietario de esta terraza ha recibido tu
                            mensaje. Podrá contactarte utilizando los datos que
                            proporcionaste.
                        </p>
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button type="button" @click="onOpenChange(false)">
                        Cerrar
                    </Button>
                </DialogFooter>
            </div>

            <form
                v-else
                class="space-y-5 p-6"
                novalidate
                @submit.prevent="submit"
            >
                <div class="grid gap-1.5">
                    <Label for="contact-message">Mensaje</Label>
                    <textarea
                        id="contact-message"
                        v-model="form.message"
                        rows="4"
                        :maxlength="maxMessageLength"
                        required
                        class="min-h-28 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :aria-invalid="Boolean(form.errors.message)"
                    />
                    <InputError :message="form.errors.message" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="contact-event-type">
                        Tipo de evento
                        <span class="text-xs font-normal text-muted-foreground">
                            (opcional)
                        </span>
                    </Label>
                    <Input
                        id="contact-event-type"
                        v-model="form.event_type"
                        maxlength="80"
                        placeholder="Cumpleaños, boda, reunión corporativa…"
                        :aria-invalid="Boolean(form.errors.event_type)"
                    />
                    <InputError :message="form.errors.event_type" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="contact-name">Nombre completo</Label>
                        <Input
                            id="contact-name"
                            v-model="form.name"
                            autocomplete="name"
                            maxlength="120"
                            required
                            :aria-invalid="Boolean(form.errors.name)"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="contact-email">Correo electrónico</Label>
                        <Input
                            id="contact-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            maxlength="255"
                            required
                            :aria-invalid="Boolean(form.errors.email)"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="contact-phone">
                            Teléfono
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                (opcional)
                            </span>
                        </Label>
                        <Input
                            id="contact-phone"
                            v-model="form.phone"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            maxlength="40"
                            placeholder="+52 33 1234 5678"
                            :aria-invalid="Boolean(form.errors.phone)"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="contact-guests">Número de invitados</Label>
                        <select
                            id="contact-guests"
                            v-model="form.guest_range"
                            required
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            :aria-invalid="Boolean(form.errors.guest_range)"
                        >
                            <option value="" disabled>
                                Selecciona un rango
                            </option>
                            <option
                                v-for="range in guestRanges"
                                :key="range.value"
                                :value="range.value"
                            >
                                {{ range.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.guest_range" />
                        <p
                            v-if="exceedsCapacity"
                            class="text-xs leading-relaxed text-amber-700"
                        >
                            La terraza tiene capacidad para {{ capacity }}
                            personas. Aun así puedes enviar tu solicitud y el
                            propietario confirmará la disponibilidad.
                        </p>
                    </div>
                </div>

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium">
                        Fecha del evento
                    </legend>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                form.date_mode === 'exact'
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="setDateMode('exact')"
                        >
                            Fecha exacta
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                form.date_mode === 'approximate'
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="setDateMode('approximate')"
                        >
                            Fecha aproximada
                        </Button>
                    </div>

                    <div v-if="form.date_mode === 'exact'" class="grid gap-1.5">
                        <Label for="contact-event-date">Fecha</Label>
                        <Input
                            id="contact-event-date"
                            v-model="form.event_date"
                            type="date"
                            :min="today"
                            :aria-invalid="Boolean(form.errors.event_date)"
                        />
                        <InputError :message="form.errors.event_date" />
                    </div>

                    <div v-else class="grid gap-1.5">
                        <Label for="contact-event-month">Mes y año</Label>
                        <Input
                            id="contact-event-month"
                            v-model="form.event_month"
                            type="month"
                            :min="currentMonth"
                            :aria-invalid="Boolean(form.errors.event_month)"
                        />
                        <InputError :message="form.errors.event_month" />
                        <p class="text-xs text-muted-foreground">
                            Si todavía no tienes una fecha exacta, indica el mes
                            aproximado.
                        </p>
                    </div>
                </fieldset>

                <div class="hidden" aria-hidden="true">
                    <Label for="contact-website">Sitio web</Label>
                    <input
                        id="contact-website"
                        v-model="form.website"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>
                <InputError :message="form.errors.website" />

                <p class="text-xs leading-relaxed text-muted-foreground">
                    Al enviar esta solicitud, aceptas que tus datos de contacto
                    sean compartidos con el propietario de este espacio para
                    responder a tu consulta.
                    <template v-if="privacyUrl">
                        Consulta el
                        <a
                            :href="privacyUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-foreground underline underline-offset-2"
                        >
                            aviso de privacidad </a
                        >.
                    </template>
                </p>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing || submitting"
                        @click="onOpenChange(false)"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || submitting"
                    >
                        <Loader2
                            v-if="form.processing || submitting"
                            class="size-4 animate-spin"
                        />
                        <Send v-else class="size-4" />
                        {{
                            form.processing || submitting
                                ? 'Enviando…'
                                : 'Enviar solicitud'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogScrollContent>
    </Dialog>
</template>
