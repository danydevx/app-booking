<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import RequiredFieldsHint from '@/components/RequiredFieldsHint.vue';
import LocationPickerMap from './LocationPickerMap.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { computed, watch } from 'vue';
import { z } from 'zod';
import { useFrontValidation } from '@/composables/useFrontValidation';
import type {
    ListingDetailField,
    ListingFormData,
    ListingLocations,
    LocationCountry,
    ServiceGroup,
} from '../types';

const props = defineProps<{
    mode: 'create' | 'edit';
    listing?: ListingFormData | null;
    serviceGroups: ServiceGroup[];
    detailFields: Record<string, ListingDetailField>;
    countries: LocationCountry[];
    locations: ListingLocations;
    urls: {
        store?: string;
        update?: string;
        index: string;
    };
}>();

const detailValues = Object.fromEntries(
    Object.keys(props.detailFields).map((key) => [
        key,
        props.listing?.details?.[key] ?? '',
    ]),
);

const form = useForm({
    name: props.listing?.name ?? '',
    description: props.listing?.description ?? '',
    address: props.listing?.address ?? '',
    city: props.listing?.city ?? '',
    state: props.listing?.state ?? '',
    country: props.listing?.country || 'México',
    postal_code: props.listing?.postal_code ?? '',
    latitude: props.listing?.latitude ?? '',
    longitude: props.listing?.longitude ?? '',
    capacity: props.listing?.capacity ?? '',
    services: props.listing?.services ?? [],
    details: detailValues,
    rules: props.listing?.rules ?? '',
    contact_name: props.listing?.contact_name ?? '',
    contact_email: props.listing?.contact_email ?? '',
    contact_phone: props.listing?.contact_phone ?? '',
    whatsapp_phone: props.listing?.whatsapp_phone ?? '',
});

const countryOptions = computed(() => props.countries);

const hasCountry = computed(() =>
    props.countries.some((country) => country.value === form.country),
);

const stateOptions = computed(() => {
    if (!hasCountry.value) {
        return [];
    }

    const options = Object.entries(props.locations).map(([value, state]) => ({
        value,
        label: state.label,
    }));

    if (form.state && !props.locations[form.state]) {
        options.unshift({
            value: form.state,
            label: `${form.state} (valor actual)`,
        });
    }

    return options;
});

const municipalityOptions = computed(() => {
    if (!hasCountry.value) {
        return [];
    }

    const municipalities = props.locations[form.state]?.municipalities ?? [];
    const options = municipalities.map((municipality) => ({
        value: municipality,
        label: municipality,
    }));

    if (form.city && !municipalities.includes(form.city)) {
        options.unshift({
            value: form.city,
            label: `${form.city} (valor actual)`,
        });
    }

    return options;
});

watch(
    () => form.country,
    (country, previousCountry) => {
        if (country === previousCountry) {
            return;
        }

        const available = props.countries.some(
            (option) => option.value === country,
        );

        if (!available) {
            form.state = '';
            form.city = '';
        }
    },
);

watch(
    () => form.state,
    (state, previousState) => {
        if (state === previousState) {
            return;
        }

        const municipalities = props.locations[state]?.municipalities ?? [];

        if (form.city !== '' && !municipalities.includes(form.city)) {
            form.city = '';
        }
    },
);

const decimalString = (message: string, min?: number, max?: number) =>
    z.string().refine((value) => {
        if (value === '') {
            return true;
        }

        if (!Number.isFinite(Number(value))) {
            return false;
        }

        const number = Number(value);

        return (
            (min === undefined || number >= min) &&
            (max === undefined || number <= max)
        );
    }, message);

const listingSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'El nombre es obligatorio.')
        .max(120, 'El nombre no puede superar 120 caracteres.'),
    description: z
        .string()
        .max(5000, 'La descripción no puede superar 5000 caracteres.'),
    address: z
        .string()
        .max(255, 'La dirección no puede superar 255 caracteres.'),
    city: z.string().max(120, 'El municipio no puede superar 120 caracteres.'),
    state: z.string().max(120, 'El estado no puede superar 120 caracteres.'),
    country: z.string().max(120, 'El país no puede superar 120 caracteres.'),
    postal_code: z
        .string()
        .max(20, 'El código postal no puede superar 20 caracteres.'),
    latitude: decimalString('Indica una latitud válida.', -90, 90),
    longitude: decimalString('Indica una longitud válida.', -180, 180),
    capacity: decimalString('Indica una capacidad válida.', 1, 100000),
    services: z.array(z.string()),
    details: z.record(z.string()),
    rules: z
        .string()
        .max(2000, 'Las reglas no pueden superar 2000 caracteres.'),
    contact_name: z
        .string()
        .max(120, 'El contacto no puede superar 120 caracteres.'),
    contact_email: z
        .string()
        .refine(
            (value) => value === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            'Indica un correo válido.',
        ),
    contact_phone: z
        .string()
        .max(40, 'El teléfono no puede superar 40 caracteres.'),
    whatsapp_phone: z
        .string()
        .max(40, 'El WhatsApp no puede superar 40 caracteres.'),
});

const { validate } = useFrontValidation(listingSchema, () => ({
    name: form.name,
    description: form.description,
    address: form.address,
    city: form.city,
    state: form.state,
    country: form.country,
    postal_code: form.postal_code,
    latitude: String(form.latitude),
    longitude: String(form.longitude),
    capacity: String(form.capacity),
    services: form.services,
    details: { ...form.details },
    rules: form.rules,
    contact_name: form.contact_name,
    contact_email: form.contact_email,
    contact_phone: form.contact_phone,
    whatsapp_phone: form.whatsapp_phone,
}));

function setCoordinates(latitude: string, longitude: string): void {
    form.latitude = latitude;
    form.longitude = longitude;
    form.clearErrors('latitude', 'longitude');
}

function toggleService(service: string): void {
    form.services = form.services.includes(service)
        ? form.services.filter((item) => item !== service)
        : [...form.services, service];
}

function detailError(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `details.${key}`
    ];
}

async function submit(): Promise<void> {
    if (!(await validate(form))) {
        return;
    }

    if (props.mode === 'create' && props.urls.store) {
        form.post(props.urls.store);

        return;
    }

    if (props.urls.update) {
        form.patch(props.urls.update);
    }
}
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit" novalidate>
        <RequiredFieldsHint />

        <section class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold">Información principal</h2>
                <p class="text-sm text-muted-foreground">
                    Lo que verán los clientes al encontrar tu terraza.
                </p>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(10rem,1fr)]"
            >
                <div class="grid gap-2">
                    <Label for="name">Nombre de la terraza</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        maxlength="120"
                        placeholder="Terraza del Jardín"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="capacity">Capacidad aproximada</Label>
                    <Input
                        id="capacity"
                        v-model.number="form.capacity"
                        type="number"
                        min="1"
                        placeholder="80"
                    />
                    <InputError :message="form.errors.capacity" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="description">Descripción</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    maxlength="5000"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Describe el espacio, el ambiente y el tipo de eventos."
                />
                <InputError :message="form.errors.description" />
            </div>
        </section>

        <section class="space-y-5 border-t pt-8">
            <div>
                <h2 class="text-lg font-semibold">Ubicación</h2>
                <p class="text-sm text-muted-foreground">
                    La dirección y las coordenadas son necesarias para publicar.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="address">Dirección</Label>
                <Input
                    id="address"
                    v-model="form.address"
                    maxlength="255"
                    placeholder="Av. Reforma 123"
                />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-2">
                    <Label for="country">País</Label>
                    <Select v-model="form.country">
                        <SelectTrigger id="country" class="w-full">
                            <SelectValue placeholder="Selecciona país" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="country in countryOptions"
                                :key="country.value"
                                :value="country.value"
                            >
                                {{ country.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.country" />
                </div>
                <div class="grid gap-2">
                    <Label for="state">Estado</Label>
                    <Select v-model="form.state" :disabled="!hasCountry">
                        <SelectTrigger id="state" class="w-full">
                            <SelectValue
                                :placeholder="
                                    hasCountry
                                        ? 'Selecciona estado'
                                        : 'Selecciona país primero'
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="state in stateOptions"
                                :key="state.value"
                                :value="state.value"
                            >
                                {{ state.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.state" />
                </div>
                <div class="grid gap-2">
                    <Label for="city">Municipio / alcaldía</Label>
                    <Select
                        v-model="form.city"
                        :disabled="municipalityOptions.length === 0"
                    >
                        <SelectTrigger id="city" class="w-full">
                            <SelectValue
                                :placeholder="
                                    form.state
                                        ? 'Selecciona municipio'
                                        : 'Selecciona estado primero'
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="municipality in municipalityOptions"
                                :key="municipality.value"
                                :value="municipality.value"
                            >
                                {{ municipality.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.city" />
                </div>
                <div class="grid gap-2">
                    <Label for="postal_code">Código postal</Label>
                    <Input
                        id="postal_code"
                        v-model="form.postal_code"
                        maxlength="20"
                    />
                    <InputError :message="form.errors.postal_code" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="latitude">Latitud</Label>
                    <Input
                        id="latitude"
                        v-model="form.latitude"
                        inputmode="decimal"
                        placeholder="19.4326"
                    />
                    <InputError :message="form.errors.latitude" />
                </div>
                <div class="grid gap-2">
                    <Label for="longitude">Longitud</Label>
                    <Input
                        id="longitude"
                        v-model="form.longitude"
                        inputmode="decimal"
                        placeholder="-99.1332"
                    />
                    <InputError :message="form.errors.longitude" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Ubicación en el mapa</Label>
                <LocationPickerMap
                    :latitude="form.latitude"
                    :longitude="form.longitude"
                    @change="setCoordinates"
                />
            </div>
        </section>

        <section class="space-y-5 border-t pt-8">
            <div>
                <h2 class="text-lg font-semibold">Características</h2>
                <p class="text-sm text-muted-foreground">
                    Selecciona lo que ya está disponible en el espacio.
                </p>
            </div>

            <div class="space-y-5">
                <div
                    v-for="group in serviceGroups"
                    :key="group.key"
                    class="space-y-3"
                >
                    <h3 class="text-sm font-semibold">{{ group.label }}</h3>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label
                            v-for="service in group.services"
                            :key="service.value"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm hover:bg-accent/40"
                        >
                            <Checkbox
                                :checked="form.services.includes(service.value)"
                                @update:checked="toggleService(service.value)"
                            />
                            <span>{{ service.label }}</span>
                        </label>
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.services" />
        </section>

        <section
            v-if="Object.keys(detailFields).length > 0"
            class="space-y-5 border-t pt-8"
        >
            <div>
                <h2 class="text-lg font-semibold">Detalles del espacio</h2>
                <p class="text-sm text-muted-foreground">
                    Información adicional para que los visitantes elijan mejor.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(field, key) in detailFields"
                    :key="key"
                    class="grid gap-2"
                >
                    <Label :for="`detail-${key}`">{{ field.label }}</Label>
                    <Select
                        v-if="field.type === 'select'"
                        v-model="form.details[key]"
                    >
                        <SelectTrigger :id="`detail-${key}`" class="w-full">
                            <SelectValue placeholder="Selecciona una opción" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(label, value) in field.options"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        v-else
                        :id="`detail-${key}`"
                        v-model="form.details[key]"
                        :placeholder="field.placeholder"
                    />
                    <InputError :message="detailError(key)" />
                </div>
            </div>
        </section>

        <section class="space-y-5 border-t pt-8">
            <div>
                <h2 class="text-lg font-semibold">Reglas y contacto</h2>
                <p class="text-sm text-muted-foreground">
                    Al menos un medio de contacto es necesario para publicar.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="rules">Reglas del espacio</Label>
                    <textarea
                        id="rules"
                        v-model="form.rules"
                        rows="6"
                        maxlength="2000"
                        class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder="No fumar, música hasta las 23:00, etc."
                    />
                    <InputError :message="form.errors.rules" />
                </div>

                <div class="grid content-start gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="contact_name">Nombre de contacto</Label>
                        <Input
                            id="contact_name"
                            v-model="form.contact_name"
                            maxlength="120"
                        />
                        <InputError :message="form.errors.contact_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_email">Correo</Label>
                        <Input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            maxlength="255"
                        />
                        <InputError :message="form.errors.contact_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_phone">Teléfono</Label>
                        <Input
                            id="contact_phone"
                            v-model="form.contact_phone"
                            maxlength="40"
                        />
                        <InputError :message="form.errors.contact_phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="whatsapp_phone">WhatsApp</Label>
                        <Input
                            id="whatsapp_phone"
                            v-model="form.whatsapp_phone"
                            maxlength="40"
                            placeholder="5215555555555"
                        />
                        <InputError :message="form.errors.whatsapp_phone" />
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3 border-t pt-6">
            <Button type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? 'Guardando...'
                        : mode === 'create'
                          ? 'Crear terraza'
                          : 'Guardar cambios'
                }}
            </Button>
            <span
                v-if="form.recentlySuccessful"
                class="text-sm text-emerald-600 dark:text-emerald-400"
            >
                Guardado.
            </span>
        </div>
    </form>
</template>
