<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
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
import type {
    ListingDetailField,
    ListingFormData,
    ServiceOption,
} from '../types';

const props = defineProps<{
    mode: 'create' | 'edit';
    listing?: ListingFormData | null;
    services: ServiceOption[];
    detailFields: Record<string, ListingDetailField>;
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
    country: props.listing?.country ?? 'México',
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

function submit(): void {
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
    <form class="space-y-8" @submit.prevent="submit">
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
                    <Label for="city">Ciudad</Label>
                    <Input id="city" v-model="form.city" maxlength="120" />
                    <InputError :message="form.errors.city" />
                </div>
                <div class="grid gap-2">
                    <Label for="state">Estado</Label>
                    <Input id="state" v-model="form.state" maxlength="120" />
                    <InputError :message="form.errors.state" />
                </div>
                <div class="grid gap-2">
                    <Label for="country">País</Label>
                    <Input
                        id="country"
                        v-model="form.country"
                        maxlength="120"
                    />
                    <InputError :message="form.errors.country" />
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
        </section>

        <section class="space-y-5 border-t pt-8">
            <div>
                <h2 class="text-lg font-semibold">Servicios</h2>
                <p class="text-sm text-muted-foreground">
                    Selecciona lo que ya está disponible en el espacio.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label
                    v-for="service in services"
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
