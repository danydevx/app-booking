<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ServiceOption } from '../../types';

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
    is_included: boolean;
    active: boolean;
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    services: CommercialService[];
    serviceOptions: ServiceOption[];
    priceUnits: ServiceOption[];
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
    is_included: false,
    price: '',
    price_unit: 'event',
    active: true,
});

const editing = ref<CommercialService | null>(null);

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
    form.is_included = item.is_included;
    form.price = item.price ?? '';
    form.price_unit = item.price_unit;
    form.active = item.active;
    form.clearErrors();
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.price_unit = 'event';
    form.active = true;
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
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Servicios adicionales</h1>
                <p class="text-sm text-muted-foreground">
                    Información comercial de servicios ofrecidos por
                    {{ listing.name }}.
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
                    {{ editing ? 'Editar servicio' : 'Nuevo servicio' }}
                </h2>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="service-key">Servicio</Label>
                        <select
                            id="service-key"
                            v-model="form.service_key"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            required
                        >
                            <option value="">
                                Selecciona una característica
                            </option>
                            <option
                                v-for="option in serviceOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.service_key"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.service_key }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="service-description">Descripción</Label>
                        <textarea
                            id="service-description"
                            v-model="form.description"
                            rows="3"
                            maxlength="2000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="service-price">Precio (MXN)</Label>
                            <Input
                                id="service-price"
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <p
                                v-if="form.errors.price"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.price }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="service-unit">Unidad de cobro</Label>
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
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="flex items-center gap-3 text-sm">
                            <Checkbox v-model:checked="form.is_included" />
                            Incluido en la renta
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <Checkbox v-model:checked="form.active" />
                            Servicio activo
                        </label>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? 'Guardar cambios' : 'Crear servicio' }}
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
                    v-if="services.length === 0"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No hay servicios comerciales configurados.
                </div>

                <article
                    v-for="item in services"
                    :key="item.id"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">{{ item.label }}</h2>
                                <Badge
                                    :variant="
                                        item.is_included
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        item.is_included
                                            ? 'Incluido'
                                            : 'Costo adicional'
                                    }}
                                </Badge>
                                <Badge v-if="!item.active" variant="outline">
                                    Inactivo
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    item.has_price
                                        ? `${item.price_formatted} · ${item.price_unit_label}`
                                        : 'Precio no especificado'
                                }}
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
                </article>
            </section>
        </div>
    </div>
</template>
