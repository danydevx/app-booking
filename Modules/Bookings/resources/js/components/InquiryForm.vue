<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { z } from 'zod';
import { useFrontValidation } from '@/composables/useFrontValidation';
import InputError from '@/components/InputError.vue';
import RequiredFieldsHint from '@/components/RequiredFieldsHint.vue';

const props = defineProps<{
    storeUrl: string;
    listingName: string;
    attribution: Record<string, string>;
}>();

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    whatsapp_phone: '',
    start_date: '',
    end_date: '',
    event_type: '',
    guests: '' as number | string,
    message: '',
    source_detail: props.attribution.source_detail ?? '',
    utm_source: props.attribution.utm_source ?? '',
    utm_medium: props.attribution.utm_medium ?? '',
    utm_campaign: props.attribution.utm_campaign ?? '',
    utm_term: props.attribution.utm_term ?? '',
    utm_content: props.attribution.utm_content ?? '',
    referrer: props.attribution.referrer ?? '',
    consent: false,
    website: '',
});

const inquirySchema = z
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
            .trim()
            .min(1, 'El teléfono es obligatorio.')
            .max(40, 'El teléfono no puede superar 40 caracteres.'),
        whatsapp_phone: z
            .string()
            .max(40, 'El WhatsApp no puede superar 40 caracteres.'),
        start_date: z.string().min(1, 'La fecha de inicio es obligatoria.'),
        end_date: z.string().min(1, 'La fecha de fin es obligatoria.'),
        event_type: z
            .string()
            .max(80, 'El tipo de evento no puede superar 80 caracteres.'),
        guests: z
            .union([z.string(), z.number()])
            .refine(
                (value) =>
                    value === '' ||
                    (Number.isInteger(Number(value)) &&
                        Number(value) >= 1 &&
                        Number(value) <= 10000),
                'Indica una cantidad válida de personas.',
            ),
        message: z
            .string()
            .max(2000, 'El mensaje no puede superar 2000 caracteres.'),
        source_detail: z.string().max(500),
        utm_source: z.string().max(500),
        utm_medium: z.string().max(500),
        utm_campaign: z.string().max(500),
        utm_term: z.string().max(500),
        utm_content: z.string().max(500),
        referrer: z.string().max(2000),
        consent: z.boolean().refine((value) => value, {
            message: 'Debes aceptar el aviso de privacidad.',
        }),
        website: z.string().max(0, 'Campo no válido.'),
    })
    .refine(
        (values) =>
            !values.start_date ||
            !values.end_date ||
            values.end_date >= values.start_date,
        {
            path: ['end_date'],
            message: 'La fecha final debe ser igual o posterior al inicio.',
        },
    );

const { validate } = useFrontValidation(inquirySchema, () => ({
    name: form.name,
    email: form.email,
    phone: form.phone,
    whatsapp_phone: form.whatsapp_phone,
    start_date: form.start_date,
    end_date: form.end_date,
    event_type: form.event_type,
    guests: form.guests,
    message: form.message,
    source_detail: form.source_detail,
    utm_source: form.utm_source,
    utm_medium: form.utm_medium,
    utm_campaign: form.utm_campaign,
    utm_term: form.utm_term,
    utm_content: form.utm_content,
    referrer: form.referrer,
    consent: form.consent,
    website: form.website,
}));

async function submit(): Promise<void> {
    if (!(await validate(form))) {
        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: false,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit" novalidate>
        <RequiredFieldsHint />

        <div>
            <h2 class="text-xl font-extrabold">Me interesa rentarla</h2>
            <p
                class="mt-1 text-sm leading-relaxed text-[color:var(--home-secondary)]"
            >
                Envía tus datos y fechas. La solicitud no bloquea el calendario
                hasta que el anfitrión la acepte.
            </p>
        </div>

        <div class="hidden" aria-hidden="true">
            <label class="home-field-label" for="website">Sitio web</label>
            <input
                id="website"
                v-model="form.website"
                class="home-control"
                tabindex="-1"
                autocomplete="off"
            />
        </div>

        <input type="hidden" v-model="form.source_detail" />
        <input type="hidden" v-model="form.utm_source" />
        <input type="hidden" v-model="form.utm_medium" />
        <input type="hidden" v-model="form.utm_campaign" />
        <input type="hidden" v-model="form.utm_term" />
        <input type="hidden" v-model="form.utm_content" />
        <input type="hidden" v-model="form.referrer" />

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label class="home-field-label" for="name">Nombre</label>
                <input
                    id="name"
                    v-model="form.name"
                    class="home-control"
                    required
                    maxlength="120"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="email">Correo</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="home-control"
                    type="email"
                    required
                    maxlength="255"
                />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="phone">Teléfono</label>
                <input
                    id="phone"
                    v-model="form.phone"
                    class="home-control"
                    required
                    maxlength="40"
                />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="whatsapp_phone">
                    WhatsApp
                </label>
                <input
                    id="whatsapp_phone"
                    v-model="form.whatsapp_phone"
                    class="home-control"
                    maxlength="40"
                />
                <InputError :message="form.errors.whatsapp_phone" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="start_date">
                    Fecha de inicio
                </label>
                <input
                    id="start_date"
                    v-model="form.start_date"
                    class="home-control"
                    type="date"
                    :min="today"
                    required
                />
                <InputError :message="form.errors.start_date" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="end_date">
                    Fecha de fin
                </label>
                <input
                    id="end_date"
                    v-model="form.end_date"
                    class="home-control"
                    type="date"
                    :min="form.start_date || today"
                    required
                />
                <InputError :message="form.errors.end_date" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="event_type">
                    Tipo de evento
                </label>
                <input
                    id="event_type"
                    v-model="form.event_type"
                    class="home-control"
                    maxlength="80"
                    placeholder="Cumpleaños, boda, reunión..."
                />
                <InputError :message="form.errors.event_type" />
            </div>
            <div class="grid gap-1.5">
                <label class="home-field-label" for="guests">Invitados</label>
                <input
                    id="guests"
                    v-model.number="form.guests"
                    class="home-control"
                    type="number"
                    min="1"
                />
                <InputError :message="form.errors.guests" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <label class="home-field-label" for="message">Mensaje</label>
            <textarea
                id="message"
                v-model="form.message"
                rows="4"
                maxlength="2000"
                class="home-control min-h-24"
                placeholder="Cuéntale al anfitrión qué necesitas."
            />
            <InputError :message="form.errors.message" />
        </div>

        <label
            class="flex items-start gap-3 text-sm leading-relaxed text-[color:var(--home-secondary)]"
        >
            <input
                v-model="form.consent"
                type="checkbox"
                class="home-checkbox mt-0.5 shrink-0"
            />
            <span>
                Acepto que el anfitrión me contacte para dar seguimiento a esta
                solicitud.
            </span>
        </label>
        <InputError :message="form.errors.consent" />
        <InputError :message="form.errors.website" />

        <button
            type="submit"
            class="home-btn home-btn--primary w-full"
            :disabled="form.processing"
        >
            {{ form.processing ? 'Enviando...' : `Solicitar ${listingName}` }}
        </button>
    </form>
</template>
