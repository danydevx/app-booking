<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Pricing = {
    price_from: string | null;
    price_weekday: string | null;
    price_weekend: string | null;
    included_hours: number | null;
    included_people: number | null;
    extra_hour: string | null;
    extra_person: string | null;
    deposit_required: string | null;
    price_notes: string | null;
    currency: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    pricing: Pricing;
    urls: {
        index: string;
        manage: string;
        edit: string;
        pricing: string;
        pricing_update: string;
        packages: string;
        services: string;
        promotions: string;
        availability: string;
        public: string | null;
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
            { title: 'Precios' },
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
    price_from: props.pricing.price_from ?? '',
    price_weekday: props.pricing.price_weekday ?? '',
    price_weekend: props.pricing.price_weekend ?? '',
    included_hours: props.pricing.included_hours ?? '',
    included_people: props.pricing.included_people ?? '',
    extra_hour: props.pricing.extra_hour ?? '',
    extra_person: props.pricing.extra_person ?? '',
    deposit_required: props.pricing.deposit_required ?? '',
    price_notes: props.pricing.price_notes ?? '',
});

const preview = computed(() => {
    const rows: Array<{ label: string; value: string }> = [];
    const money = (value: string | number): string =>
        `$${Number(value).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;

    if (form.price_from !== '') {
        rows.push({ label: 'Desde', value: money(form.price_from) });
    }
    if (form.price_weekday !== '') {
        rows.push({
            label: 'Lunes a jueves',
            value: money(form.price_weekday),
        });
    }
    if (form.price_weekend !== '') {
        rows.push({
            label: 'Viernes a domingo',
            value: money(form.price_weekend),
        });
    }
    if (form.extra_hour !== '') {
        rows.push({
            label: 'Hora adicional',
            value: money(form.extra_hour),
        });
    }
    if (form.extra_person !== '') {
        rows.push({
            label: 'Persona adicional',
            value: money(form.extra_person),
        });
    }
    if (form.deposit_required !== '') {
        rows.push({
            label: 'Anticipo requerido',
            value: money(form.deposit_required),
        });
    }

    return rows;
});

function submit(): void {
    form.patch(props.urls.pricing_update, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Precios · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Precios y tarifas</h1>
                <p class="text-sm text-muted-foreground">
                    Información comercial de {{ listing.name }}. Los precios son
                    informativos y no calculan reservas ni cotizaciones.
                </p>
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
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <form
                class="grid gap-6 rounded-xl border bg-card p-5"
                @submit.prevent="submit"
            >
                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="price-from">Precio desde (MXN)</Label>
                        <Input
                            id="price-from"
                            v-model="form.price_from"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.price_from"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.price_from }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="price-weekday">Lunes a jueves</Label>
                        <Input
                            id="price-weekday"
                            v-model="form.price_weekday"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.price_weekday"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.price_weekday }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="price-weekend">Viernes a domingo</Label>
                        <Input
                            id="price-weekend"
                            v-model="form.price_weekend"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.price_weekend"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.price_weekend }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-4 border-t pt-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="included-hours">Horas incluidas</Label>
                        <Input
                            id="included-hours"
                            v-model.number="form.included_hours"
                            type="number"
                            min="1"
                            max="168"
                        />
                        <p
                            v-if="form.errors.included_hours"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.included_hours }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="included-people">Personas incluidas</Label>
                        <Input
                            id="included-people"
                            v-model.number="form.included_people"
                            type="number"
                            min="1"
                        />
                        <p
                            v-if="form.errors.included_people"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.included_people }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-4 border-t pt-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="extra-hour">Hora adicional (MXN)</Label>
                        <Input
                            id="extra-hour"
                            v-model="form.extra_hour"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.extra_hour"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.extra_hour }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="extra-person"
                            >Persona adicional (MXN)</Label
                        >
                        <Input
                            id="extra-person"
                            v-model="form.extra_person"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.extra_person"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.extra_person }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-4 border-t pt-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="deposit-required">
                            Anticipo requerido (MXN)
                        </Label>
                        <Input
                            id="deposit-required"
                            v-model="form.deposit_required"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p
                            v-if="form.errors.deposit_required"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.deposit_required }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-2 border-t pt-5">
                    <Label for="price-notes">Condiciones del precio</Label>
                    <textarea
                        id="price-notes"
                        v-model="form.price_notes"
                        rows="4"
                        maxlength="2000"
                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder="Ej. Precios por evento de 8 horas. No incluye servicios adicionales."
                    />
                    <p
                        v-if="form.errors.price_notes"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.price_notes }}
                    </p>
                </section>

                <div class="border-t pt-5">
                    <Button type="submit" :disabled="form.processing">
                        Guardar precios
                    </Button>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Vista previa</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Así se presentará la información en la ficha pública.
                    </p>
                    <div v-if="preview.length > 0" class="mt-4 space-y-2">
                        <div
                            v-for="row in preview"
                            :key="row.label"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ row.label }}
                            </span>
                            <span class="font-medium">{{ row.value }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Sin precios configurados.
                    </p>
                    <div
                        v-if="
                            form.included_hours !== '' ||
                            form.included_people !== ''
                        "
                        class="mt-4 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground"
                    >
                        Incluye
                        <span v-if="form.included_hours !== ''">
                            {{ form.included_hours }} horas
                        </span>
                        <span
                            v-if="
                                form.included_hours !== '' &&
                                form.included_people !== ''
                            "
                        >
                            para
                        </span>
                        <span v-if="form.included_people !== ''">
                            {{ form.included_people }} personas
                        </span>
                        .
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
