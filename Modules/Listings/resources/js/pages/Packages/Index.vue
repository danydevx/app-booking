<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    amenities: Array<{ key: string; label: string }>;
    services: Array<{ key: string; label: string }>;
    conditions: string | null;
    active: boolean;
    sort_order: number;
    included_amenity_keys: string[];
    included_service_keys: string[];
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    packages: ListingPackage[];
    amenityGroups: ServiceGroup[];
    serviceGroups: ServiceGroup[];
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
    included_amenities: [] as string[],
    included_services: [] as string[],
    conditions: '',
    active: true,
    sort_order: '',
});

const editing = ref<ListingPackage | null>(null);

function toggleAmenity(key: string): void {
    form.included_amenities = form.included_amenities.includes(key)
        ? form.included_amenities.filter((item) => item !== key)
        : [...form.included_amenities, key];
}

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
    form.included_amenities = [...item.included_amenity_keys];
    form.included_services = [...item.included_service_keys];
    form.conditions = item.conditions ?? '';
    form.active = item.active;
    form.sort_order = String(item.sort_order);
    form.clearErrors();
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.active = true;
    form.duration_hours = 8;
    form.included_people = 50;
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
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Paquetes de renta</h1>
                <p class="text-sm text-muted-foreground">
                    Opciones comerciales informativas para {{ listing.name }}.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.manage">Volver al resumen</Link>
            </Button>
        </div>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,25rem)_minmax(0,1fr)]"
        >
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">
                    {{ editing ? 'Editar paquete' : 'Nuevo paquete' }}
                </h2>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
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
                        />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="package-price">Precio (MXN)</Label>
                            <Input
                                id="package-price"
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <p
                                v-if="form.errors.price"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.price }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="package-hours">Duración (horas)</Label>
                            <Input
                                id="package-hours"
                                v-model.number="form.duration_hours"
                                type="number"
                                min="1"
                                max="168"
                                required
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="package-people"
                                >Personas incluidas</Label
                            >
                            <Input
                                id="package-people"
                                v-model.number="form.included_people"
                                type="number"
                                min="1"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="package-order">Orden</Label>
                            <Input
                                id="package-order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Amenidades incluidas</Label>
                        <div class="space-y-3 rounded-lg border p-3">
                            <div
                                v-for="group in amenityGroups"
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
                                                form.included_amenities.includes(
                                                    service.value,
                                                )
                                            "
                                            @update:checked="
                                                toggleAmenity(service.value)
                                            "
                                        />
                                        {{ service.label }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Servicios incluidos</Label>
                        <div class="space-y-3 rounded-lg border p-3">
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
                    </div>

                    <div class="grid gap-2">
                        <Label for="package-conditions">Condiciones</Label>
                        <textarea
                            id="package-conditions"
                            v-model="form.conditions"
                            rows="3"
                            maxlength="2000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>

                    <label class="flex items-center gap-3 text-sm">
                        <Checkbox v-model:checked="form.active" />
                        Paquete activo
                    </label>

                    <p
                        v-if="form.errors.active"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.active }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? 'Guardar cambios' : 'Crear paquete' }}
                        </Button>
                        <Button
                            v-if="editing"
                            type="button"
                            variant="secondary"
                            @click="reset"
                        >
                            Cancelar
                        </Button>
                    </div>
                </form>
            </section>

            <section class="space-y-3">
                <div
                    v-if="packages.length === 0"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No hay paquetes configurados.
                </div>

                <article
                    v-for="item in packages"
                    :key="item.id"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">{{ item.name }}</h2>
                                <Badge
                                    :variant="
                                        item.active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ item.active ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ item.price_formatted }} ·
                                {{ item.duration_hours }} horas ·
                                {{ item.included_people }} personas
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="edit(item)"
                            >
                                Editar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="destroy(item)"
                            >
                                Eliminar
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="item.description"
                        class="mt-3 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ item.description }}
                    </p>
                    <div
                        v-if="item.amenities.length > 0"
                        class="mt-3 flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="amenity in item.amenities"
                            :key="amenity.key"
                            variant="secondary"
                        >
                            {{ amenity.label }}
                        </Badge>
                    </div>
                    <div
                        v-if="item.services.length > 0"
                        class="mt-2 flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="service in item.services"
                            :key="service.key"
                            variant="outline"
                        >
                            {{ service.label }}
                        </Badge>
                    </div>
                    <p
                        v-if="item.conditions"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        {{ item.conditions }}
                    </p>
                </article>
            </section>
        </div>
    </div>
</template>
