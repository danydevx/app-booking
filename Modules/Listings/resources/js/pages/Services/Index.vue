<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    Check,
    Info,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ServiceGroup, ServiceOption } from '../../types';

type CommercialService = {
    id: number;
    service_key: string;
    label: string;
    description: string | null;
    price: string | null;
    price_formatted: string | null;
    has_price: boolean;
    price_unit: string;
    price_unit_label: string;
    pricing_mode: string;
    pricing_mode_label: string;
    provider: string;
    provider_label: string;
    is_included: boolean;
    active: boolean;
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    services: CommercialService[];
    serviceGroups: ServiceGroup[];
    priceUnits: ServiceOption[];
    pricingModes: ServiceOption[];
    providers: ServiceOption[];
    urls: {
        index: string;
        manage: string;
        edit: string;
        pricing: string;
        packages: string;
        services: string;
        promotions: string;
        availability: string;
        public: string | null;
        store: string;
    };
}>();

defineOptions({
    layout: (props: {
        listing: { name: string };
        urls: {
            index: string;
            manage: string;
            edit: string;
            pricing: string;
            packages: string;
            services: string;
            promotions: string;
            availability: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Servicios' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Precios', href: props.urls.pricing },
            { title: 'Paquetes', href: props.urls.packages },
            { title: 'Servicios', href: props.urls.services },
            { title: 'Promociones', href: props.urls.promotions },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Fotos y datos', href: props.urls.edit },
        ],
    }),
});

const form = useForm({
    service_key: '',
    description: '',
    pricing_mode: 'additional',
    provider: 'own',
    price: '',
    price_unit: 'event',
    active: true,
});

const editing = ref<CommercialService | null>(null);

const includedCount = computed(
    () =>
        props.services.filter((item) => item.pricing_mode === 'included')
            .length,
);

const additionalCount = computed(
    () =>
        props.services.filter((item) => item.pricing_mode === 'additional')
            .length,
);

const quoteCount = computed(
    () => props.services.filter((item) => item.pricing_mode === 'quote').length,
);

const formServiceGroups = computed(() => {
    const groups = props.serviceGroups.map((group) => ({
        ...group,
        services: [...group.services],
    }));

    if (!editing.value) {
        return groups;
    }

    const key = editing.value.service_key;
    const known = groups.some((group) =>
        group.services.some((service) => service.value === key),
    );

    if (!known) {
        groups.push({
            key: 'legacy',
            label: 'Otros servicios',
            services: [{ value: key, label: editing.value.label }],
        });
    }

    return groups;
});

watch(
    () => form.pricing_mode,
    (mode) => {
        if (mode !== 'additional') {
            form.price = '';
        }
    },
);

function pricingModeVariant(mode: string): 'default' | 'secondary' | 'outline' {
    if (mode === 'included') {
        return 'default';
    }

    if (mode === 'additional') {
        return 'secondary';
    }

    return 'outline';
}

function servicePriceLabel(item: CommercialService): string {
    if (item.pricing_mode === 'additional' && item.has_price) {
        return item.price_formatted ?? 'No especificado';
    }

    if (item.pricing_mode === 'quote') {
        return 'A consultar';
    }

    return 'Incluido';
}

function serviceGroupLabel(key: string): string | null {
    return (
        props.serviceGroups.find((group) =>
            group.services.some((service) => service.value === key),
        )?.label ?? null
    );
}

function submit(): void {
    if (editing.value) {
        form.patch(editing.value.update_url, {
            preserveScroll: true,
            onSuccess: reset,
        });

        return;
    }

    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: reset,
    });
}

function edit(item: CommercialService): void {
    editing.value = item;
    form.service_key = item.service_key;
    form.description = item.description ?? '';
    form.pricing_mode = item.pricing_mode;
    form.provider = item.provider;
    form.price = item.price ?? '';
    form.price_unit = item.price_unit;
    form.active = item.active;
    form.clearErrors();

    document
        .getElementById('service-form')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.pricing_mode = 'additional';
    form.provider = 'own';
    form.price_unit = 'event';
    form.active = true;
}

function focusForm(): void {
    document
        .getElementById('service-form')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('service-key')?.focus({ preventScroll: true });
}

function destroy(item: CommercialService): void {
    if (!window.confirm(`¿Eliminar el servicio ${item.label}?`)) {
        return;
    }

    router.delete(item.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Servicios · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Sparkles class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            Servicios adicionales
                        </h1>
                        <Badge variant="outline">Informativo</Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Servicios comerciales de {{ listing.name }} que pueden
                        incluirse en la renta o cobrarse aparte.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.manage">Volver al resumen</Link>
                </Button>
                <Button v-if="urls.public" variant="outline" as-child>
                    <a
                        :href="urls.public"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver pública
                    </a>
                </Button>
            </div>
        </header>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Sparkles class="size-4" />
                    Servicios
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ services.length }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ services.length === 1 ? 'Configurado' : 'Configurados' }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Check class="size-4" />
                    Incluidos
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ includedCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    En la renta del espacio
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <BadgeDollarSign class="size-4" />
                    Costo adicional
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ additionalCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Se cobran por separado
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Info class="size-4" />
                    A consultar
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ quoteCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Requieren cotización
                </p>
            </div>
        </section>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]"
        >
            <section class="overflow-hidden rounded-xl border bg-card">
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b p-5"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Plus class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">
                                {{
                                    editing
                                        ? 'Editar servicio'
                                        : 'Nuevo servicio'
                                }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    editing
                                        ? 'Modifica los datos y guarda los cambios.'
                                        : 'Agrega un servicio a la oferta de la terraza.'
                                }}
                            </p>
                        </div>
                    </div>
                    <Badge v-if="editing" variant="outline">Editando</Badge>
                </div>

                <form
                    id="service-form"
                    class="grid gap-5 p-5"
                    @submit.prevent="submit"
                >
                    <section class="grid gap-4">
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Datos del servicio
                            </h3>
                        </div>
                        <div class="grid gap-2">
                            <Label for="service-key">Servicio</Label>
                            <select
                                id="service-key"
                                v-model="form.service_key"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                required
                            >
                                <option value="">Selecciona un servicio</option>
                                <optgroup
                                    v-for="group in formServiceGroups"
                                    :key="group.key"
                                    :label="group.label"
                                >
                                    <option
                                        v-for="service in group.services"
                                        :key="service.value"
                                        :value="service.value"
                                    >
                                        {{ service.label }}
                                    </option>
                                </optgroup>
                            </select>
                            <p
                                v-if="form.errors.service_key"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.service_key }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="service-description">
                                Descripción
                            </Label>
                            <textarea
                                id="service-description"
                                v-model="form.description"
                                rows="3"
                                maxlength="2000"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Detalla qué incluye o cómo se cobra."
                            />
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </section>

                    <section class="grid gap-4 border-t pt-5">
                        <div class="flex items-center gap-2">
                            <BadgeDollarSign class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Modalidad y proveedor
                            </h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="service-pricing-mode">
                                    Modalidad de precio
                                </Label>
                                <select
                                    id="service-pricing-mode"
                                    v-model="form.pricing_mode"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="mode in pricingModes"
                                        :key="mode.value"
                                        :value="mode.value"
                                    >
                                        {{ mode.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.pricing_mode"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.pricing_mode }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="service-provider">
                                    Proveedor
                                </Label>
                                <select
                                    id="service-provider"
                                    v-model="form.provider"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="provider in providers"
                                        :key="provider.value"
                                        :value="provider.value"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.provider"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.provider }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="form.pricing_mode === 'additional'"
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <div class="grid gap-2">
                                <Label for="service-price">Precio (MXN)</Label>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                                    >
                                        $
                                    </span>
                                    <Input
                                        id="service-price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="pl-7"
                                    />
                                </div>
                                <p
                                    v-if="form.errors.price"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.price }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="service-unit">
                                    Unidad de cobro
                                </Label>
                                <select
                                    id="service-unit"
                                    v-model="form.price_unit"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="unit in priceUnits"
                                        :key="unit.value"
                                        :value="unit.value"
                                    >
                                        {{ unit.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.price_unit"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.price_unit }}
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            El precio solo es obligatorio para servicios con
                            costo adicional.
                        </p>
                    </section>

                    <section class="grid gap-4 border-t pt-5">
                        <div class="flex items-center gap-2">
                            <Info class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">Visibilidad</h3>
                        </div>
                        <div class="rounded-lg border bg-muted/20 p-3">
                            <label class="flex items-center gap-3 text-sm">
                                <Checkbox v-model:checked="form.active" />
                                Disponible y visible en la ficha pública
                            </label>
                        </div>
                        <p
                            v-if="form.errors.active"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.active }}
                        </p>
                    </section>

                    <div class="flex flex-wrap justify-end gap-2 border-t pt-5">
                        <Button
                            v-if="editing"
                            type="button"
                            variant="secondary"
                            @click="reset"
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Check class="size-4" />
                            {{ editing ? 'Guardar cambios' : 'Crear servicio' }}
                        </Button>
                    </div>
                </form>
            </section>

            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Servicios configurados
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Servicios que se muestran en la ficha pública.
                        </p>
                    </div>
                    <Badge variant="secondary">
                        {{ services.length }}
                        {{ services.length === 1 ? 'servicio' : 'servicios' }}
                    </Badge>
                </div>

                <div
                    v-if="services.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center"
                >
                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Sparkles class="size-6" />
                    </div>
                    <p class="mt-3 font-medium">Sin servicios configurados</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Agrega servicios como DJ, banquetes o mobiliario
                        adicional.
                    </p>
                    <Button
                        type="button"
                        class="mt-4"
                        variant="outline"
                        @click="focusForm"
                    >
                        <Plus class="size-4" />
                        Crear primer servicio
                    </Button>
                </div>

                <div v-else class="grid items-start gap-4 lg:grid-cols-2">
                    <article
                        v-for="item in services"
                        :key="item.id"
                        class="flex flex-col overflow-hidden rounded-xl border bg-card"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <Sparkles class="size-4" />
                                </div>
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3 class="font-semibold">
                                            {{ item.label }}
                                        </h3>
                                        <Badge
                                            :variant="
                                                pricingModeVariant(
                                                    item.pricing_mode,
                                                )
                                            "
                                        >
                                            {{ item.pricing_mode_label }}
                                        </Badge>
                                        <Badge
                                            v-if="!item.active"
                                            variant="outline"
                                        >
                                            Inactivo
                                        </Badge>
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{
                                            serviceGroupLabel(
                                                item.service_key,
                                            ) ?? 'Otros servicios'
                                        }}
                                        · {{ item.provider_label }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-right">
                                <span
                                    class="block text-[0.65rem] font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Precio
                                </span>
                                <span class="font-semibold">
                                    {{ servicePriceLabel(item) }}
                                </span>
                                <span
                                    v-if="
                                        item.pricing_mode === 'additional' &&
                                        item.has_price
                                    "
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ item.price_unit_label }}
                                </span>
                            </p>
                        </div>

                        <div class="flex-1 space-y-3 p-4">
                            <p
                                v-if="item.description"
                                class="text-sm whitespace-pre-line text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Sin descripción adicional.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 border-t p-3">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="edit(item)"
                            >
                                <Pencil class="size-4" />
                                Editar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                @click="destroy(item)"
                            >
                                <Trash2 class="size-4" />
                                Eliminar
                            </Button>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
