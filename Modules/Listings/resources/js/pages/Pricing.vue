<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    CalendarDays,
    CalendarRange,
    Check,
    Clock,
    Copy,
    Layers,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { z } from 'zod';
import { useFrontValidation } from '@/composables/useFrontValidation';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RequiredFieldsHint from '@/components/RequiredFieldsHint.vue';

type PricingDailyRow = {
    hours: number | null;
    entry_time: string | null;
    exit_time: string | null;
    price: string | null;
};

type Pricing = {
    price_from: string | null;
    daily_pricing: Record<string, PricingDailyRow>;
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
    daily_pricing: Record<
        string,
        {
            hours: number | null;
            entry_time: string | null;
            exit_time: string | null;
            price: string | null;
        }
    >;
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

type PricingForm = {
    price_from: string;
    daily_pricing: Record<
        string,
        {
            hours: string;
            entry_time: string;
            exit_time: string;
            price: string;
        }
    >;
    included_hours: string | number;
    included_people: string | number;
    extra_hour: string;
    extra_person: string;
    deposit_required: string;
    price_notes: string;
};

type DailyFormRow = {
    hours: string;
    entry_time: string;
    exit_time: string;
    price: string;
};

const dayFields = [
    { key: 'monday', label: 'Lunes' },
    { key: 'tuesday', label: 'Martes' },
    { key: 'wednesday', label: 'Miércoles' },
    { key: 'thursday', label: 'Jueves' },
    { key: 'friday', label: 'Viernes' },
    { key: 'saturday', label: 'Sábado' },
    { key: 'sunday', label: 'Domingo' },
] as const;

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

const dailyPricing = Object.fromEntries(
    dayFields.map((day) => {
        const row = props.pricing.daily_pricing[day.key];

        return [
            day.key,
            {
                hours:
                    row?.hours !== null && row?.hours !== undefined
                        ? String(row.hours)
                        : '',
                entry_time: row?.entry_time ?? '',
                exit_time: row?.exit_time ?? '',
                price: row?.price ?? '',
            },
        ];
    }),
);

const form = useForm<PricingForm>({
    price_from: props.pricing.price_from ?? '',
    daily_pricing: dailyPricing,
    included_hours: props.pricing.included_hours ?? '',
    included_people: props.pricing.included_people ?? '',
    extra_hour: props.pricing.extra_hour ?? '',
    extra_person: props.pricing.extra_person ?? '',
    deposit_required: props.pricing.deposit_required ?? '',
    price_notes: props.pricing.price_notes ?? '',
});

function dailyError(
    day: string,
    field: 'hours' | 'entry_time' | 'exit_time' | 'price',
): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `daily_pricing.${day}.${field}`
    ];
}

function rangeDailyError(
    day: string,
    field: 'hours' | 'entry_time' | 'exit_time' | 'price',
): string | undefined {
    return (rangeForm.errors as Record<string, string | undefined>)[
        `daily_pricing.${day}.${field}`
    ];
}

const baseFromPrice = computed(() => {
    const from = formatCurrency(form.price_from);

    if (from) {
        return from;
    }

    const prices = dayFields
        .map((day) => form.daily_pricing[day.key]?.price)
        .filter((price): price is string => Boolean(price))
        .map((price) => Number(price))
        .filter((amount) => Number.isFinite(amount) && amount > 0);

    return prices.length > 0 ? formatCurrency(Math.min(...prices)) : null;
});

const baseDailyPriceCount = computed(
    () =>
        dayFields.filter((day) => Boolean(form.daily_pricing[day.key]?.price))
            .length,
);

const hasBasePricing = computed(
    () =>
        baseFromPrice.value !== null ||
        baseDailyPriceCount.value > 0 ||
        form.included_hours !== '' ||
        form.included_people !== '' ||
        form.extra_hour !== '' ||
        form.extra_person !== '' ||
        form.deposit_required !== '' ||
        form.price_notes.trim() !== '',
);

const currentRangeCount = computed(
    () =>
        props.priceRanges.filter((range) => range.status.value === 'current')
            .length,
);

const upcomingRange = computed(
    () =>
        props.priceRanges.find((range) => range.status.value === 'upcoming') ??
        null,
);

const preview = computed(() => {
    const rows: Array<{ label: string; value: string }> = [];

    for (const day of dayFields) {
        const row = form.daily_pricing[day.key];
        const parts: string[] = [];

        if (row?.hours) {
            parts.push(`${row.hours} h`);
        }
        if (row?.entry_time || row?.exit_time) {
            parts.push(`${row.entry_time || '—'} a ${row.exit_time || '—'}`);
        }

        const price = formatCurrency(row?.price);
        if (price) {
            parts.push(price);
        }

        if (parts.length > 0) {
            rows.push({ label: day.label, value: parts.join(' · ') });
        }
    }

    const extraHour = formatCurrency(form.extra_hour);
    if (extraHour) {
        rows.push({ label: 'Hora adicional', value: extraHour });
    }

    const extraPerson = formatCurrency(form.extra_person);
    if (extraPerson) {
        rows.push({ label: 'Persona adicional', value: extraPerson });
    }

    const deposit = formatCurrency(form.deposit_required);
    if (deposit) {
        rows.push({ label: 'Anticipo requerido', value: deposit });
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
    daily_pricing: emptyDailyPricing(),
    included_people: '',
    extra_hour: '',
    extra_person: '',
    deposit_required: '',
    conditions: '',
    active: true,
});

function emptyDailyPricing(): Record<string, DailyFormRow> {
    return Object.fromEntries(
        dayFields.map((day) => [
            day.key,
            { hours: '', entry_time: '', exit_time: '', price: '' },
        ]),
    );
}

const editingRange = ref<PriceRange | null>(null);
const rangeDialogOpen = ref(false);

const numericString = (message: string) =>
    z
        .string()
        .refine(
            (value) =>
                value === '' ||
                (Number.isFinite(Number(value)) && Number(value) >= 0),
            message,
        );

const rangeDailySchema = z.object({
    hours: z
        .string()
        .refine(
            (value) =>
                value === '' ||
                (Number.isInteger(Number(value)) &&
                    Number(value) >= 1 &&
                    Number(value) <= 24),
            'Indica horas entre 1 y 24.',
        ),
    entry_time: z
        .string()
        .refine(
            (value) => value === '' || /^([01]\d|2[0-3]):[0-5]\d$/.test(value),
            'Indica una hora de entrada válida.',
        ),
    exit_time: z
        .string()
        .refine(
            (value) => value === '' || /^([01]\d|2[0-3]):[0-5]\d$/.test(value),
            'Indica una hora de salida válida.',
        ),
    price: numericString('Indica un precio válido.'),
});

const rangeSchema = z
    .object({
        name: z
            .string()
            .trim()
            .min(1, 'El nombre es obligatorio.')
            .max(120, 'El nombre no puede superar 120 caracteres.'),
        starts_at: z.string().min(1, 'La fecha de inicio es obligatoria.'),
        ends_at: z.string().min(1, 'La fecha de fin es obligatoria.'),
        daily_pricing: z.record(rangeDailySchema),
        included_people: z
            .string()
            .refine(
                (value) =>
                    value === '' ||
                    (Number.isInteger(Number(value)) && Number(value) >= 1),
                'Indica una cantidad válida de personas.',
            ),
        extra_hour: numericString('Indica un costo válido.'),
        extra_person: numericString('Indica un costo válido.'),
        deposit_required: numericString('Indica un anticipo válido.'),
        conditions: z
            .string()
            .max(2000, 'Las condiciones no pueden superar 2000 caracteres.'),
        active: z.boolean(),
    })
    .refine(
        (values) =>
            !values.starts_at ||
            !values.ends_at ||
            values.ends_at >= values.starts_at,
        {
            path: ['ends_at'],
            message: 'La fecha final debe ser igual o posterior al inicio.',
        },
    )
    .refine(
        (values) =>
            Object.values(values.daily_pricing).some(
                (row) => row.price.trim() !== '',
            ),
        {
            path: ['daily_pricing'],
            message: 'Define el precio de al menos un día.',
        },
    );

const { validate } = useFrontValidation(rangeSchema, () => ({
    name: rangeForm.name,
    starts_at: rangeForm.starts_at,
    ends_at: rangeForm.ends_at,
    daily_pricing: rangeForm.daily_pricing,
    included_people: rangeForm.included_people,
    extra_hour: rangeForm.extra_hour,
    extra_person: rangeForm.extra_person,
    deposit_required: rangeForm.deposit_required,
    conditions: rangeForm.conditions,
    active: rangeForm.active,
}));

function openCreateRange(): void {
    resetRange();
    rangeDialogOpen.value = true;
}

async function submitRange(): Promise<void> {
    if (!(await validate(rangeForm))) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            rangeDialogOpen.value = false;
            resetRange();
        },
    };

    if (editingRange.value) {
        rangeForm.patch(editingRange.value.update_url, options);

        return;
    }

    rangeForm.post(props.urls.range_store, options);
}

function editRange(range: PriceRange): void {
    editingRange.value = range;
    rangeForm.name = range.name;
    rangeForm.starts_at = range.starts_at;
    rangeForm.ends_at = range.ends_at;
    rangeForm.daily_pricing = Object.fromEntries(
        dayFields.map((day) => {
            const row = range.daily_pricing[day.key];

            return [
                day.key,
                {
                    hours:
                        row?.hours !== null && row?.hours !== undefined
                            ? String(row.hours)
                            : '',
                    entry_time: row?.entry_time ?? '',
                    exit_time: row?.exit_time ?? '',
                    price: row?.price ?? '',
                },
            ];
        }),
    );
    rangeForm.included_people =
        range.included_people !== null ? String(range.included_people) : '';
    rangeForm.extra_hour = range.extra_hour ?? '';
    rangeForm.extra_person = range.extra_person ?? '';
    rangeForm.deposit_required = range.deposit_required ?? '';
    rangeForm.conditions = range.conditions ?? '';
    rangeForm.active = range.active;
    rangeForm.clearErrors();
    rangeDialogOpen.value = true;
}

function handleRangeDialogOpen(open: boolean): void {
    rangeDialogOpen.value = open;

    if (!open) {
        resetRange();
    }
}

function resetRange(): void {
    editingRange.value = null;
    rangeForm.reset();
    rangeForm.clearErrors();
    rangeForm.daily_pricing = emptyDailyPricing();
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

function rowHasData(row: DailyFormRow | undefined): boolean {
    return Boolean(
        row?.hours || row?.entry_time || row?.exit_time || row?.price,
    );
}

function copyDayToAll(target: 'base' | 'range', dayKey: string): void {
    const rows =
        target === 'base' ? form.daily_pricing : rangeForm.daily_pricing;
    const row = rows[dayKey];

    if (!row || !rowHasData(row)) {
        return;
    }

    for (const day of dayFields) {
        if (day.key === dayKey) {
            continue;
        }

        rows[day.key] = { ...row };
    }
}

function rangeHasData(range: PriceRange, day: string): boolean {
    const row = range.daily_pricing[day];

    return Boolean(
        row && (row.price || row.hours || row.entry_time || row.exit_time),
    );
}

function formatRangePrice(value: string | null): string {
    return formatCurrency(value) ?? '—';
}
</script>

<template>
    <Head :title="`Precios · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <BadgeDollarSign class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            Precios y tarifas
                        </h1>
                        <Badge variant="outline">Informativo</Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Información comercial de {{ listing.name }}. Los precios
                        no calculan reservas ni cotizaciones.
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
                    <BadgeDollarSign class="size-4" />
                    Precio base desde
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ baseFromPrice ?? 'Sin definir' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        baseFromPrice
                            ? 'MXN · precio de referencia'
                            : 'Agrega un precio orientativo'
                    }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <CalendarDays class="size-4" />
                    Días con tarifa
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ baseDailyPriceCount }}
                    <span class="text-sm font-normal text-muted-foreground">
                        de 7
                    </span>
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        baseDailyPriceCount === 7
                            ? 'Semana completa'
                            : 'Completa los días que apliquen'
                    }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Layers class="size-4" />
                    Temporadas
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ priceRanges.length }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ currentRangeCount }}
                    {{ currentRangeCount === 1 ? 'vigente' : 'vigentes' }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <CalendarRange class="size-4" />
                    Próxima temporada
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{
                        upcomingRange
                            ? upcomingRange.starts_at_label
                            : 'Sin programar'
                    }}
                </p>
                <p class="truncate text-xs text-muted-foreground">
                    {{
                        upcomingRange
                            ? upcomingRange.name
                            : 'Crea una tarifa por fechas'
                    }}
                </p>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
            <form
                class="overflow-hidden rounded-xl border bg-card"
                @submit.prevent="submit"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b p-5"
                >
                    <div>
                        <h2 class="text-lg font-semibold">Precio base</h2>
                        <p class="text-sm text-muted-foreground">
                            Se muestra en la ficha pública cuando no hay una
                            temporada aplicable.
                        </p>
                    </div>
                    <Badge :variant="hasBasePricing ? 'default' : 'outline'">
                        {{ hasBasePricing ? 'Configurado' : 'Pendiente' }}
                    </Badge>
                </div>

                <div class="grid gap-6 p-5">
                    <section class="grid gap-4">
                        <div class="flex items-center gap-2">
                            <Wallet class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Precio de referencia
                            </h3>
                        </div>
                        <div class="grid gap-2 sm:max-w-xs">
                            <Label for="price-from">Precio desde (MXN)</Label>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                                >
                                    $
                                </span>
                                <Input
                                    id="price-from"
                                    v-model="form.price_from"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="pl-7"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Referencia general si no defines un precio por
                                día.
                            </p>
                            <p
                                v-if="form.errors.price_from"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.price_from }}
                            </p>
                        </div>
                    </section>

                    <section class="grid gap-3 border-t pt-6">
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <CalendarDays class="size-4 text-primary" />
                                    <h3 class="text-sm font-semibold">
                                        Tarifa por día
                                    </h3>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Horas, horario de entrada/salida y precio
                                    para cada día.
                                </p>
                            </div>
                            <Badge variant="secondary">
                                {{ baseDailyPriceCount }} de 7 días
                            </Badge>
                        </div>

                        <div class="overflow-x-auto rounded-lg border">
                            <table class="w-full min-w-[46rem] text-sm">
                                <thead
                                    class="bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            Día
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Horas
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Entrada
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Salida
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Precio
                                        </th>
                                        <th class="w-10 px-3 py-2">
                                            <span class="sr-only">
                                                Acciones
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="day in dayFields"
                                        :key="day.key"
                                        class="border-t"
                                    >
                                        <td class="px-3 py-2">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="font-medium">
                                                    {{ day.label }}
                                                </span>
                                                <span
                                                    v-if="
                                                        form.daily_pricing[
                                                            day.key
                                                        ]?.price
                                                    "
                                                    class="size-1.5 rounded-full bg-primary"
                                                    title="Con precio"
                                                ></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    form.daily_pricing[day.key]
                                                        .hours
                                                "
                                                type="number"
                                                min="1"
                                                max="24"
                                                placeholder="8"
                                                class="w-20"
                                            />
                                            <p
                                                v-if="
                                                    dailyError(day.key, 'hours')
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    dailyError(day.key, 'hours')
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    form.daily_pricing[day.key]
                                                        .entry_time
                                                "
                                                type="time"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    dailyError(
                                                        day.key,
                                                        'entry_time',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    dailyError(
                                                        day.key,
                                                        'entry_time',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    form.daily_pricing[day.key]
                                                        .exit_time
                                                "
                                                type="time"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    dailyError(
                                                        day.key,
                                                        'exit_time',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    dailyError(
                                                        day.key,
                                                        'exit_time',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    form.daily_pricing[day.key]
                                                        .price
                                                "
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    dailyError(day.key, 'price')
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    dailyError(day.key, 'price')
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="
                                                    !rowHasData(
                                                        form.daily_pricing[
                                                            day.key
                                                        ],
                                                    )
                                                "
                                                title="Aplicar este día a todos"
                                                aria-label="Aplicar este día a todos"
                                                @click="
                                                    copyDayToAll(
                                                        'base',
                                                        day.key,
                                                    )
                                                "
                                            >
                                                <Copy class="size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="grid gap-4 border-t pt-6">
                        <div class="flex items-center gap-2">
                            <Clock class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">Incluye</h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="included-hours">
                                    Horas incluidas
                                </Label>
                                <Input
                                    id="included-hours"
                                    v-model.number="form.included_hours"
                                    type="number"
                                    min="1"
                                    max="168"
                                    placeholder="8"
                                />
                                <p
                                    v-if="form.errors.included_hours"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.included_hours }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="included-people">
                                    Personas incluidas
                                </Label>
                                <Input
                                    id="included-people"
                                    v-model.number="form.included_people"
                                    type="number"
                                    min="1"
                                    placeholder="50"
                                />
                                <p
                                    v-if="form.errors.included_people"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.included_people }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 border-t pt-6">
                        <div class="flex items-center gap-2">
                            <Wallet class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Cargos adicionales
                            </h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="extra-hour">
                                    Hora adicional (MXN)
                                </Label>
                                <Input
                                    id="extra-hour"
                                    v-model="form.extra_hour"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="form.errors.extra_hour"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.extra_hour }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="extra-person">
                                    Persona adicional (MXN)
                                </Label>
                                <Input
                                    id="extra-person"
                                    v-model="form.extra_person"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="form.errors.extra_person"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.extra_person }}
                                </p>
                            </div>
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
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="form.errors.deposit_required"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.deposit_required }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-2 border-t pt-6">
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
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t bg-muted/20 p-4"
                >
                    <p class="text-xs text-muted-foreground">
                        Los cambios se guardan manualmente.
                    </p>
                    <Button type="submit" :disabled="form.processing">
                        <Check class="size-4" />
                        Guardar precios
                    </Button>
                </div>
            </form>

            <aside class="xl:sticky xl:top-6">
                <section class="overflow-hidden rounded-xl border bg-card">
                    <div class="flex items-start gap-3 border-b p-5">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Sparkles class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">
                                Vista previa
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Así se presenta en la ficha pública.
                            </p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="rounded-xl border bg-muted/20 p-4">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Desde
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ baseFromPrice ?? 'Sin definir' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                MXN · precio informativo
                            </p>

                            <div
                                v-if="preview.length > 0"
                                class="mt-4 divide-y rounded-lg border bg-card"
                            >
                                <div
                                    v-for="row in preview"
                                    :key="row.label"
                                    class="flex items-start justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        {{ row.label }}
                                    </span>
                                    <span class="text-right font-medium">
                                        {{ row.value }}
                                    </span>
                                </div>
                            </div>
                            <p
                                v-else
                                class="mt-4 rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
                            >
                                Aún no hay tarifas por día ni cargos
                                adicionales.
                            </p>

                            <div
                                v-if="
                                    form.included_hours !== '' ||
                                    form.included_people !== ''
                                "
                                class="mt-4 flex flex-wrap gap-2 text-xs"
                            >
                                <span
                                    v-if="form.included_hours !== ''"
                                    class="inline-flex items-center gap-1 rounded-full border bg-card px-2 py-1"
                                >
                                    <Clock class="size-3" />
                                    {{ form.included_hours }} horas
                                </span>
                                <span
                                    v-if="form.included_people !== ''"
                                    class="inline-flex items-center gap-1 rounded-full border bg-card px-2 py-1"
                                >
                                    <Users class="size-3" />
                                    {{ form.included_people }} personas
                                </span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-muted-foreground">
                            Los precios son informativos y no se usan para
                            calcular reservas.
                        </p>
                    </div>
                </section>
            </aside>
        </div>

        <section class="overflow-hidden rounded-xl border bg-card">
            <div
                class="flex flex-wrap items-start justify-between gap-3 border-b p-5"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <CalendarRange class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">
                            Tarifas por temporada
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Precios por rangos de fechas, por ejemplo temporada
                            alta, vacaciones o fechas especiales.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">
                        {{ priceRanges.length }}
                        {{ priceRanges.length === 1 ? 'tarifa' : 'tarifas' }}
                    </Badge>
                    <Button type="button" size="sm" @click="openCreateRange">
                        <Plus class="size-4" />
                        Nueva tarifa
                    </Button>
                </div>
            </div>

            <div class="grid gap-5 p-5">
                <div
                    v-if="priceRanges.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center"
                >
                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <CalendarRange class="size-6" />
                    </div>
                    <p class="mt-3 font-medium">Sin tarifas por temporada</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea una tarifa para aplicar precios distintos en fechas
                        especiales.
                    </p>
                    <Button
                        type="button"
                        class="mt-4"
                        variant="outline"
                        @click="openCreateRange"
                    >
                        <Plus class="size-4" />
                        Crear primera tarifa
                    </Button>
                </div>

                <div v-else class="grid items-start gap-4 xl:grid-cols-2">
                    <article
                        v-for="range in priceRanges"
                        :key="range.id"
                        class="flex flex-col overflow-hidden rounded-xl border bg-card"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <CalendarRange class="size-4" />
                                </div>
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
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
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ range.starts_at_label }} —
                                        {{ range.ends_at_label }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-right">
                                <span
                                    class="block text-[0.65rem] font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Desde
                                </span>
                                <span class="font-semibold">
                                    {{ range.price_formatted }}
                                </span>
                            </p>
                        </div>

                        <div class="flex-1 space-y-3 p-4">
                            <div
                                v-if="
                                    dayFields.some((day) =>
                                        rangeHasData(range, day.key),
                                    )
                                "
                                class="overflow-x-auto rounded-lg border"
                            >
                                <table class="w-full min-w-[32rem] text-sm">
                                    <thead
                                        class="bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase"
                                    >
                                        <tr>
                                            <th class="px-3 py-2 font-medium">
                                                Día
                                            </th>
                                            <th class="px-3 py-2 font-medium">
                                                Horas
                                            </th>
                                            <th class="px-3 py-2 font-medium">
                                                Entrada
                                            </th>
                                            <th class="px-3 py-2 font-medium">
                                                Salida
                                            </th>
                                            <th
                                                class="px-3 py-2 text-right font-medium"
                                            >
                                                Precio
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template
                                            v-for="day in dayFields"
                                            :key="day.key"
                                        >
                                            <tr
                                                v-if="
                                                    rangeHasData(range, day.key)
                                                "
                                                class="border-t"
                                            >
                                                <td
                                                    class="px-3 py-2 font-medium"
                                                >
                                                    {{ day.label }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        range.daily_pricing[
                                                            day.key
                                                        ]?.hours ?? '—'
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        range.daily_pricing[
                                                            day.key
                                                        ]?.entry_time ?? '—'
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        range.daily_pricing[
                                                            day.key
                                                        ]?.exit_time ?? '—'
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-right font-medium"
                                                >
                                                    {{
                                                        formatRangePrice(
                                                            range.daily_pricing[
                                                                day.key
                                                            ]?.price ?? null,
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p
                                v-else
                                class="rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
                            >
                                Esta tarifa aún no tiene precios por día.
                            </p>

                            <div
                                v-if="
                                    range.included_people ||
                                    range.extra_hour_formatted ||
                                    range.extra_person_formatted ||
                                    range.deposit_required_formatted
                                "
                                class="flex flex-wrap gap-2 text-xs"
                            >
                                <span
                                    v-if="range.included_people"
                                    class="inline-flex items-center gap-1 rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    <Users class="size-3" />
                                    {{ range.included_people }} personas
                                </span>
                                <span
                                    v-if="range.extra_hour_formatted"
                                    class="rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    Hora adicional:
                                    {{ range.extra_hour_formatted }}
                                </span>
                                <span
                                    v-if="range.extra_person_formatted"
                                    class="rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    Persona adicional:
                                    {{ range.extra_person_formatted }}
                                </span>
                                <span
                                    v-if="range.deposit_required_formatted"
                                    class="rounded-full border bg-muted/30 px-2 py-1"
                                >
                                    Anticipo:
                                    {{ range.deposit_required_formatted }}
                                </span>
                            </div>

                            <p
                                v-if="range.conditions"
                                class="rounded-lg bg-muted/30 p-2 text-xs text-muted-foreground"
                            >
                                {{ range.conditions }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 border-t p-3">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="editRange(range)"
                            >
                                <Pencil class="size-4" />
                                Editar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                @click="destroyRange(range)"
                            >
                                <Trash2 class="size-4" />
                                Eliminar
                            </Button>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <Dialog :open="rangeDialogOpen" @update:open="handleRangeDialogOpen">
            <DialogScrollContent class="sm:max-w-4xl">
                <form class="grid gap-5" @submit.prevent="submitRange">
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingRange ? 'Editar tarifa' : 'Nueva tarifa'
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                editingRange
                                    ? 'Modifica el rango de fechas y los precios por día.'
                                    : 'Define el rango de fechas y el precio de cada día.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <RequiredFieldsHint />
                    <div
                        class="flex flex-wrap items-start justify-between gap-2 xl:col-span-2"
                    >
                        <div>
                            <h3 class="font-semibold">
                                {{
                                    editingRange
                                        ? 'Editar tarifa'
                                        : 'Nueva tarifa'
                                }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    editingRange
                                        ? 'Modifica los datos y guarda los cambios.'
                                        : 'Define el rango de fechas y el precio de cada día.'
                                }}
                            </p>
                        </div>
                        <Badge v-if="editingRange" variant="outline">
                            Editando
                        </Badge>
                    </div>

                    <div class="grid content-start gap-4">
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

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="range-people">
                                    Personas incluidas
                                </Label>
                                <Input
                                    id="range-people"
                                    v-model="rangeForm.included_people"
                                    type="number"
                                    min="1"
                                    placeholder="50"
                                />
                                <p
                                    v-if="rangeForm.errors.included_people"
                                    class="text-sm text-destructive"
                                >
                                    {{ rangeForm.errors.included_people }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="range-deposit">
                                    Anticipo requerido
                                </Label>
                                <Input
                                    id="range-deposit"
                                    v-model="rangeForm.deposit_required"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="rangeForm.errors.deposit_required"
                                    class="text-sm text-destructive"
                                >
                                    {{ rangeForm.errors.deposit_required }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="range-extra-hour">
                                    Hora adicional
                                </Label>
                                <Input
                                    id="range-extra-hour"
                                    v-model="rangeForm.extra_hour"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="rangeForm.errors.extra_hour"
                                    class="text-sm text-destructive"
                                >
                                    {{ rangeForm.errors.extra_hour }}
                                </p>
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
                                    placeholder="0.00"
                                />
                                <p
                                    v-if="rangeForm.errors.extra_person"
                                    class="text-sm text-destructive"
                                >
                                    {{ rangeForm.errors.extra_person }}
                                </p>
                            </div>
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
                            <p
                                v-if="rangeForm.errors.conditions"
                                class="text-sm text-destructive"
                            >
                                {{ rangeForm.errors.conditions }}
                            </p>
                        </div>

                        <label class="flex items-center gap-3 text-sm">
                            <Checkbox v-model:checked="rangeForm.active" />
                            Tarifa activa
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                :disabled="rangeForm.processing"
                            >
                                <Check class="size-4" />
                                {{
                                    editingRange
                                        ? 'Guardar tarifa'
                                        : 'Crear tarifa'
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
                    </div>

                    <div class="grid content-start gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <CalendarDays class="size-4 text-primary" />
                                <Label>Precio por día</Label>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                Usa <Copy class="inline size-3" /> para copiar
                                un día a toda la semana.
                            </span>
                        </div>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="w-full min-w-[40rem] text-sm">
                                <thead
                                    class="bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            Día
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Horas
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Entrada
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Salida
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            Precio
                                        </th>
                                        <th class="w-10 px-3 py-2">
                                            <span class="sr-only">
                                                Acciones
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="day in dayFields"
                                        :key="day.key"
                                        class="border-t"
                                    >
                                        <td class="px-3 py-2">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="font-medium">
                                                    {{ day.label }}
                                                </span>
                                                <span
                                                    v-if="
                                                        rangeForm.daily_pricing[
                                                            day.key
                                                        ]?.price
                                                    "
                                                    class="size-1.5 rounded-full bg-primary"
                                                    title="Con precio"
                                                ></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    rangeForm.daily_pricing[
                                                        day.key
                                                    ].hours
                                                "
                                                type="number"
                                                min="1"
                                                max="24"
                                                placeholder="8"
                                                class="w-20"
                                            />
                                            <p
                                                v-if="
                                                    rangeDailyError(
                                                        day.key,
                                                        'hours',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    rangeDailyError(
                                                        day.key,
                                                        'hours',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    rangeForm.daily_pricing[
                                                        day.key
                                                    ].entry_time
                                                "
                                                type="time"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    rangeDailyError(
                                                        day.key,
                                                        'entry_time',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    rangeDailyError(
                                                        day.key,
                                                        'entry_time',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    rangeForm.daily_pricing[
                                                        day.key
                                                    ].exit_time
                                                "
                                                type="time"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    rangeDailyError(
                                                        day.key,
                                                        'exit_time',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    rangeDailyError(
                                                        day.key,
                                                        'exit_time',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="
                                                    rangeForm.daily_pricing[
                                                        day.key
                                                    ].price
                                                "
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="w-32"
                                            />
                                            <p
                                                v-if="
                                                    rangeDailyError(
                                                        day.key,
                                                        'price',
                                                    )
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    rangeDailyError(
                                                        day.key,
                                                        'price',
                                                    )
                                                }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="
                                                    !rowHasData(
                                                        rangeForm.daily_pricing[
                                                            day.key
                                                        ],
                                                    )
                                                "
                                                title="Aplicar este día a todos"
                                                aria-label="Aplicar este día a todos"
                                                @click="
                                                    copyDayToAll(
                                                        'range',
                                                        day.key,
                                                    )
                                                "
                                            >
                                                <Copy class="size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p
                            v-if="rangeForm.errors.daily_pricing"
                            class="text-sm text-destructive"
                        >
                            {{ rangeForm.errors.daily_pricing }}
                        </p>
                    </div>
                </form>
            </DialogScrollContent>
        </Dialog>
    </div>
</template>
