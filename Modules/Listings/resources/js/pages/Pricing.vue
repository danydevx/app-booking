<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Pricing = {
    price_from: string | null;
    price_monday: string | null;
    price_tuesday: string | null;
    price_wednesday: string | null;
    price_thursday: string | null;
    price_friday: string | null;
    price_saturday: string | null;
    price_sunday: string | null;
    included_hours: number | null;
    included_people: number | null;
    extra_hour: string | null;
    extra_person: string | null;
    deposit_required: string | null;
    price_notes: string | null;
    currency: string;
};

type PriceRange = {
    id: number;
    name: string;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    price: string | null;
    price_formatted: string;
    included_hours: number | null;
    included_people: number | null;
    extra_hour: string | null;
    extra_person: string | null;
    deposit_required: string | null;
    extra_hour_formatted: string | null;
    extra_person_formatted: string | null;
    deposit_required_formatted: string | null;
    conditions: string | null;
    active: boolean;
    status: { value: string; label: string };
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    pricing: Pricing;
    priceRanges: PriceRange[];
    urls: {
        index: string;
        manage: string;
        edit: string;
        pricing: string;
        pricing_update: string;
        range_store: string;
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
    price_monday: props.pricing.price_monday ?? '',
    price_tuesday: props.pricing.price_tuesday ?? '',
    price_wednesday: props.pricing.price_wednesday ?? '',
    price_thursday: props.pricing.price_thursday ?? '',
    price_friday: props.pricing.price_friday ?? '',
    price_saturday: props.pricing.price_saturday ?? '',
    price_sunday: props.pricing.price_sunday ?? '',
    included_hours: props.pricing.included_hours ?? '',
    included_people: props.pricing.included_people ?? '',
    extra_hour: props.pricing.extra_hour ?? '',
    extra_person: props.pricing.extra_person ?? '',
    deposit_required: props.pricing.deposit_required ?? '',
    price_notes: props.pricing.price_notes ?? '',
});

const dayFields: Array<{
    key:
        | 'price_monday'
        | 'price_tuesday'
        | 'price_wednesday'
        | 'price_thursday'
        | 'price_friday'
        | 'price_saturday'
        | 'price_sunday';
    label: string;
}> = [
    { key: 'price_monday', label: 'Lunes' },
    { key: 'price_tuesday', label: 'Martes' },
    { key: 'price_wednesday', label: 'Miércoles' },
    { key: 'price_thursday', label: 'Jueves' },
    { key: 'price_friday', label: 'Viernes' },
    { key: 'price_saturday', label: 'Sábado' },
    { key: 'price_sunday', label: 'Domingo' },
];

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

    for (const day of dayFields) {
        if (form[day.key] !== '') {
            rows.push({
                label: day.label,
                value: money(form[day.key]),
            });
        }
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

const rangeForm = useForm({
    name: '',
    starts_at: '',
    ends_at: '',
    price: '',
    included_hours: '',
    included_people: '',
    extra_hour: '',
    extra_person: '',
    deposit_required: '',
    conditions: '',
    active: true,
});

const editingRange = ref<PriceRange | null>(null);

function submitRange(): void {
    if (editingRange.value) {
        rangeForm.patch(editingRange.value.update_url, {
            preserveScroll: true,
            onSuccess: resetRange,
        });

        return;
    }

    rangeForm.post(props.urls.range_store, {
        preserveScroll: true,
        onSuccess: resetRange,
    });
}

function editRange(range: PriceRange): void {
    editingRange.value = range;
    rangeForm.name = range.name;
    rangeForm.starts_at = range.starts_at;
    rangeForm.ends_at = range.ends_at;
    rangeForm.price = range.price ?? '';
    rangeForm.included_hours =
        range.included_hours !== null ? String(range.included_hours) : '';
    rangeForm.included_people =
        range.included_people !== null ? String(range.included_people) : '';
    rangeForm.extra_hour = range.extra_hour ?? '';
    rangeForm.extra_person = range.extra_person ?? '';
    rangeForm.deposit_required = range.deposit_required ?? '';
    rangeForm.conditions = range.conditions ?? '';
    rangeForm.active = range.active;
    rangeForm.clearErrors();
}

function resetRange(): void {
    editingRange.value = null;
    rangeForm.reset();
    rangeForm.active = true;
}

function destroyRange(range: PriceRange): void {
    if (!window.confirm(`¿Eliminar la tarifa ${range.name}?`)) {
        return;
    }

    router.delete(range.delete_url, { preserveScroll: true });
}

function rangeStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'current') {
        return 'default';
    }

    if (status === 'expired') {
        return 'destructive';
    }

    if (status === 'upcoming') {
        return 'secondary';
    }

    return 'outline';
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
                <section class="grid gap-4 border-b pb-5">
                    <div class="grid gap-2 sm:max-w-xs">
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

                    <div>
                        <h3 class="text-sm font-semibold">Precio por día</h3>
                        <p class="text-sm text-muted-foreground">
                            Define el precio para cada día de la semana.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="day in dayFields"
                            :key="day.key"
                            class="grid gap-2"
                        >
                            <Label :for="day.key">{{ day.label }}</Label>
                            <Input
                                :id="day.key"
                                v-model="form[day.key]"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <p
                                v-if="form.errors[day.key]"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors[day.key] }}
                            </p>
                        </div>
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

        <section class="rounded-xl border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Tarifas por temporada</h2>
                    <p class="text-sm text-muted-foreground">
                        Precios por rangos de fechas, por ejemplo temporada
                        alta, vacaciones o fechas especiales.
                    </p>
                </div>
            </div>

            <div
                class="mt-5 grid items-start gap-6 xl:grid-cols-[minmax(0,25rem)_minmax(0,1fr)]"
            >
                <form
                    class="grid gap-4 rounded-xl border bg-muted/20 p-4"
                    @submit.prevent="submitRange"
                >
                    <h3 class="font-semibold">
                        {{ editingRange ? 'Editar tarifa' : 'Nueva tarifa' }}
                    </h3>
                    <div class="grid gap-2">
                        <Label for="range-name">Nombre</Label>
                        <Input
                            id="range-name"
                            v-model="rangeForm.name"
                            maxlength="120"
                            required
                            placeholder="Temporada alta"
                        />
                        <p
                            v-if="rangeForm.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ rangeForm.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="range-start">Inicio</Label>
                            <Input
                                id="range-start"
                                v-model="rangeForm.starts_at"
                                type="date"
                                required
                            />
                            <p
                                v-if="rangeForm.errors.starts_at"
                                class="text-sm text-destructive"
                            >
                                {{ rangeForm.errors.starts_at }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="range-end">Fin</Label>
                            <Input
                                id="range-end"
                                v-model="rangeForm.ends_at"
                                type="date"
                                required
                            />
                            <p
                                v-if="rangeForm.errors.ends_at"
                                class="text-sm text-destructive"
                            >
                                {{ rangeForm.errors.ends_at }}
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="range-price">Precio (MXN)</Label>
                        <Input
                            id="range-price"
                            v-model="rangeForm.price"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                        />
                        <p
                            v-if="rangeForm.errors.price"
                            class="text-sm text-destructive"
                        >
                            {{ rangeForm.errors.price }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="range-hours">Horas incluidas</Label>
                            <Input
                                id="range-hours"
                                v-model="rangeForm.included_hours"
                                type="number"
                                min="1"
                                max="168"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="range-people">Personas incluidas</Label>
                            <Input
                                id="range-people"
                                v-model="rangeForm.included_people"
                                type="number"
                                min="1"
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="range-extra-hour">Hora adicional</Label>
                            <Input
                                id="range-extra-hour"
                                v-model="rangeForm.extra_hour"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="range-extra-person">
                                Persona adicional
                            </Label>
                            <Input
                                id="range-extra-person"
                                v-model="rangeForm.extra_person"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="range-deposit">Anticipo requerido</Label>
                        <Input
                            id="range-deposit"
                            v-model="rangeForm.deposit_required"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="range-conditions">Condiciones</Label>
                        <textarea
                            id="range-conditions"
                            v-model="rangeForm.conditions"
                            rows="3"
                            maxlength="2000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <label class="flex items-center gap-3 text-sm">
                        <Checkbox v-model:checked="rangeForm.active" />
                        Tarifa activa
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="rangeForm.processing">
                            {{
                                editingRange ? 'Guardar tarifa' : 'Crear tarifa'
                            }}
                        </Button>
                        <Button
                            v-if="editingRange"
                            type="button"
                            variant="secondary"
                            @click="resetRange"
                        >
                            Cancelar
                        </Button>
                    </div>
                </form>

                <div class="space-y-3">
                    <div
                        v-if="priceRanges.length === 0"
                        class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        No hay tarifas por temporada configuradas.
                    </div>

                    <article
                        v-for="range in priceRanges"
                        :key="range.id"
                        class="rounded-xl border bg-card p-4"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">
                                        {{ range.name }}
                                    </h3>
                                    <Badge
                                        :variant="
                                            rangeStatusVariant(
                                                range.status.value,
                                            )
                                        "
                                    >
                                        {{ range.status.label }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ range.starts_at_label }} al
                                    {{ range.ends_at_label }} ·
                                    {{ range.price_formatted }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editRange(range)"
                                >
                                    Editar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="destroyRange(range)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                        <p
                            v-if="range.included_hours || range.included_people"
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            Incluye
                            <span v-if="range.included_hours">
                                {{ range.included_hours }} horas
                            </span>
                            <span
                                v-if="
                                    range.included_hours &&
                                    range.included_people
                                "
                            >
                                para
                            </span>
                            <span v-if="range.included_people">
                                {{ range.included_people }} personas
                            </span>
                            .
                        </p>
                        <p
                            v-if="
                                range.extra_hour_formatted ||
                                range.extra_person_formatted ||
                                range.deposit_required_formatted
                            "
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            <span v-if="range.extra_hour_formatted">
                                Hora adicional:
                                {{ range.extra_hour_formatted }}
                            </span>
                            <span v-if="range.extra_person_formatted">
                                · Persona adicional:
                                {{ range.extra_person_formatted }}
                            </span>
                            <span v-if="range.deposit_required_formatted">
                                · Anticipo:
                                {{ range.deposit_required_formatted }}
                            </span>
                        </p>
                        <p
                            v-if="range.conditions"
                            class="mt-2 text-xs text-muted-foreground"
                        >
                            {{ range.conditions }}
                        </p>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
