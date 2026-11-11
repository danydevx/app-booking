<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    Check,
    Clock,
    Home,
    Info,
    Package,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ServiceGroup } from '../../types';

type ListingPackage = {
    id: number;
    name: string;
    description: string | null;
    price: string | null;
    price_formatted: string;
    duration_hours: number;
    included_people: number;
    services: Array<{ key: string; label: string }>;
    conditions: string | null;
    active: boolean;
    sort_order: number;
    included_service_keys: string[];
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    packages: ListingPackage[];
    serviceGroups: ServiceGroup[];
    characteristics: ServiceGroup[];
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
            { title: 'Paquetes' },
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
    name: '',
    description: '',
    price: '',
    duration_hours: 8,
    included_people: 50,
    included_services: [] as string[],
    conditions: '',
    active: true,
    sort_order: '',
});

const editing = ref<ListingPackage | null>(null);

function formatCurrency(
    value: string | number | null | undefined,
): string | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const amount = Number(value);

    if (!Number.isFinite(amount)) {
        return null;
    }

    return `$${amount.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

const activeCount = computed(
    () => props.packages.filter((item) => item.active).length,
);

const lowestPrice = computed(() => {
    const prices = props.packages
        .map((item) => Number(item.price))
        .filter((amount) => Number.isFinite(amount) && amount >= 0);

    return prices.length > 0 ? formatCurrency(Math.min(...prices)) : null;
});

const distinctServiceCount = computed(() => {
    const keys = new Set<string>();

    for (const item of props.packages) {
        for (const service of item.services) {
            keys.add(service.key);
        }
    }

    return keys.size;
});

const selectedServiceCount = computed(() => form.included_services.length);

const characteristicCount = computed(() =>
    props.characteristics.reduce(
        (total, group) => total + group.services.length,
        0,
    ),
);

function toggleService(key: string): void {
    form.included_services = form.included_services.includes(key)
        ? form.included_services.filter((item) => item !== key)
        : [...form.included_services, key];
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

function edit(item: ListingPackage): void {
    editing.value = item;
    form.name = item.name;
    form.description = item.description ?? '';
    form.price = item.price ?? '';
    form.duration_hours = item.duration_hours;
    form.included_people = item.included_people;
    form.included_services = [...item.included_service_keys];
    form.conditions = item.conditions ?? '';
    form.active = item.active;
    form.sort_order = String(item.sort_order);
    form.clearErrors();

    document
        .getElementById('package-form')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.active = true;
    form.duration_hours = 8;
    form.included_people = 50;
}

function focusForm(): void {
    document
        .getElementById('package-form')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('package-name')?.focus({ preventScroll: true });
}

function destroy(item: ListingPackage): void {
    if (!window.confirm(`¿Eliminar el paquete ${item.name}?`)) {
        return;
    }

    router.delete(item.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Paquetes · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Package class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            Paquetes de renta
                        </h1>
                        <Badge variant="outline">Informativo</Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Combina precio, duración y servicios para
                        {{ listing.name }}. No calculan reservas ni pagos.
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
                    <Package class="size-4" />
                    Paquetes
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ packages.length }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ packages.length === 1 ? 'Configurado' : 'Configurados' }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Check class="size-4" />
                    Activos
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ activeCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Visibles en la ficha pública
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <BadgeDollarSign class="size-4" />
                    Precio más bajo
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ lowestPrice ?? 'Sin definir' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        lowestPrice ? 'MXN · por paquete' : 'Agrega un paquete'
                    }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Sparkles class="size-4" />
                    Servicios incluidos
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ distinctServiceCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Distintos entre paquetes
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border bg-card">
            <div
                class="flex flex-wrap items-start justify-between gap-3 border-b p-4 sm:p-5"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Home class="size-4" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold">
                                Características incluidas
                            </h2>
                            <Badge variant="secondary">
                                {{ characteristicCount }}
                                {{
                                    characteristicCount === 1
                                        ? 'característica'
                                        : 'características'
                                }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Pertenecen a la terraza y aplican a todos los
                            paquetes; no se configuran por paquete.
                        </p>
                    </div>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link :href="urls.edit">
                        <Pencil class="size-4" />
                        Editar en Fotos y datos
                    </Link>
                </Button>
            </div>

            <div
                v-if="characteristics.length > 0"
                class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4"
            >
                <div v-for="group in characteristics" :key="group.key">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <Badge
                            v-for="service in group.services"
                            :key="service.value"
                            variant="secondary"
                        >
                            {{ service.label }}
                        </Badge>
                    </div>
                </div>
            </div>
            <p v-else class="p-4 text-sm text-muted-foreground sm:p-5">
                La terraza aún no tiene características configuradas.
                <Link
                    :href="urls.edit"
                    class="font-medium underline underline-offset-2"
                    >Configúralas en Fotos y datos</Link
                >.
            </p>
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
                                    editing ? 'Editar paquete' : 'Nuevo paquete'
                                }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    editing
                                        ? 'Modifica los datos y guarda los cambios.'
                                        : 'Crea una opción comercial con precio, capacidad y servicios.'
                                }}
                            </p>
                        </div>
                    </div>
                    <Badge v-if="editing" variant="outline">Editando</Badge>
                </div>

                <form
                    id="package-form"
                    class="grid gap-5 p-5"
                    @submit.prevent="submit"
                >
                    <section class="grid gap-4">
                        <div class="flex items-center gap-2">
                            <Package class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Datos del paquete
                            </h3>
                        </div>
                        <div class="grid gap-2">
                            <Label for="package-name">Nombre</Label>
                            <Input
                                id="package-name"
                                v-model="form.name"
                                maxlength="120"
                                required
                                placeholder="Paquete Familiar"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="package-description">Descripción</Label>
                            <textarea
                                id="package-description"
                                v-model="form.description"
                                rows="3"
                                maxlength="2000"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Describe qué incluye el paquete."
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
                                Precio y capacidad
                            </h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="package-price">Precio (MXN)</Label>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                                    >
                                        $
                                    </span>
                                    <Input
                                        id="package-price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
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
                                <Label for="package-hours">
                                    Duración (horas)
                                </Label>
                                <Input
                                    id="package-hours"
                                    v-model.number="form.duration_hours"
                                    type="number"
                                    min="1"
                                    max="168"
                                    required
                                />
                                <p
                                    v-if="form.errors.duration_hours"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.duration_hours }}
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="package-people">
                                    Personas incluidas
                                </Label>
                                <Input
                                    id="package-people"
                                    v-model.number="form.included_people"
                                    type="number"
                                    min="1"
                                    required
                                />
                                <p
                                    v-if="form.errors.included_people"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.included_people }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="package-order">Orden</Label>
                                <Input
                                    id="package-order"
                                    v-model="form.sort_order"
                                    type="number"
                                    min="0"
                                    placeholder="Automático"
                                />
                                <p
                                    v-if="form.errors.sort_order"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.sort_order }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-3 border-t pt-5">
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <Sparkles class="size-4 text-primary" />
                                    <h3 class="text-sm font-semibold">
                                        Servicios incluidos
                                    </h3>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Elige servicios del catálogo comercial. Las
                                    características del lugar ya se incluyen en
                                    todos los paquetes.
                                </p>
                            </div>
                            <Badge variant="secondary">
                                {{ selectedServiceCount }} seleccionados
                            </Badge>
                        </div>

                        <div
                            v-if="serviceGroups.length === 0"
                            class="rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            No hay servicios comerciales disponibles.
                            <Link
                                :href="urls.services"
                                class="font-medium underline underline-offset-2"
                                >Créalos en Servicios</Link
                            >.
                        </div>
                        <div v-else class="space-y-3 rounded-lg border p-3">
                            <div
                                v-for="group in serviceGroups"
                                :key="group.key"
                            >
                                <p
                                    class="mb-1 text-xs font-medium text-muted-foreground"
                                >
                                    {{ group.label }}
                                </p>
                                <div class="grid gap-1 sm:grid-cols-2">
                                    <label
                                        v-for="service in group.services"
                                        :key="service.value"
                                        class="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            :checked="
                                                form.included_services.includes(
                                                    service.value,
                                                )
                                            "
                                            @update:checked="
                                                toggleService(service.value)
                                            "
                                        />
                                        {{ service.label }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.included_services"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.included_services }}
                        </p>
                    </section>

                    <section class="grid gap-4 border-t pt-5">
                        <div class="flex items-center gap-2">
                            <Info class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Condiciones y visibilidad
                            </h3>
                        </div>
                        <div class="grid gap-2">
                            <Label for="package-conditions">Condiciones</Label>
                            <textarea
                                id="package-conditions"
                                v-model="form.conditions"
                                rows="3"
                                maxlength="2000"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Ej. Anticipo no reembolsable."
                            />
                            <p
                                v-if="form.errors.conditions"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.conditions }}
                            </p>
                        </div>
                        <label class="flex items-center gap-3 text-sm">
                            <Checkbox v-model:checked="form.active" />
                            Paquete activo y visible en la ficha pública
                        </label>
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
                            {{ editing ? 'Guardar cambios' : 'Crear paquete' }}
                        </Button>
                    </div>
                </form>
            </section>

            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Paquetes configurados
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Opciones que se muestran en la ficha pública. Todos
                            incluyen las características del lugar.
                        </p>
                    </div>
                    <Badge variant="secondary">
                        {{ packages.length }}
                        {{ packages.length === 1 ? 'paquete' : 'paquetes' }}
                    </Badge>
                </div>

                <div
                    v-if="packages.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center"
                >
                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Package class="size-6" />
                    </div>
                    <p class="mt-3 font-medium">Sin paquetes configurados</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea un paquete para ofrecer una opción cerrada de
                        renta.
                    </p>
                    <Button
                        type="button"
                        class="mt-4"
                        variant="outline"
                        @click="focusForm"
                    >
                        <Plus class="size-4" />
                        Crear primer paquete
                    </Button>
                </div>

                <div v-else class="grid items-start gap-4 lg:grid-cols-2">
                    <article
                        v-for="item in packages"
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
                                    <Package class="size-4" />
                                </div>
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3 class="font-semibold">
                                            {{ item.name }}
                                        </h3>
                                        <Badge
                                            :variant="
                                                item.active
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{
                                                item.active
                                                    ? 'Activo'
                                                    : 'Inactivo'
                                            }}
                                        </Badge>
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Orden {{ item.sort_order }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-right">
                                <span
                                    class="block text-[0.65rem] font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Precio
                                </span>
                                <span class="text-lg font-semibold">
                                    {{ item.price_formatted }}
                                </span>
                            </p>
                        </div>

                        <div class="flex-1 space-y-3 p-4">
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    <Clock class="size-3" />
                                    {{ item.duration_hours }} horas
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    <Users class="size-3" />
                                    {{ item.included_people }} personas
                                </span>
                            </div>

                            <p
                                v-if="item.description"
                                class="text-sm whitespace-pre-line text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>

                            <div
                                v-if="item.services.length > 0"
                                class="space-y-1"
                            >
                                <p
                                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Servicios incluidos
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="service in item.services"
                                        :key="service.key"
                                        variant="outline"
                                    >
                                        {{ service.label }}
                                    </Badge>
                                </div>
                            </div>
                            <p v-else class="text-xs text-muted-foreground">
                                Sin servicios adicionales incluidos.
                            </p>

                            <p
                                v-if="item.conditions"
                                class="rounded-lg bg-muted/30 p-2 text-xs text-muted-foreground"
                            >
                                {{ item.conditions }}
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
