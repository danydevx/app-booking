<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarCheck,
    CalendarDays,
    CalendarOff,
    Clock,
    Lock,
    Repeat,
    Trash2,
    Unlock,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AvailabilityCalendar from '../../components/AvailabilityCalendar.vue';
import type { BlockedDate, StatusOption } from '../../types';

type BlockSeries = {
    id: number;
    reason: string | null;
    recurrence: string;
    recurrence_label: string;
    weekdays: number[];
    starts_on: string;
    ends_on: string;
    unavailabilities_count: number;
    destroy_url: string;
};

const props = defineProps<{
    listing: {
        id: number;
        name: string;
        slug: string;
        status: string;
        status_label: string;
        timezone: string;
    };
    blocked_dates: BlockedDate[];
    series: BlockSeries[];
    recurrences: StatusOption[];
    horizon_days: number;
    urls: {
        index: string;
        bookings: string;
        manage: string;
        edit: string;
        customers: string;
        availability: string;
        block_range: string;
        unblock: string;
        unblock_range: string;
        series_store: string;
        public_listing: string;
    };
}>();

type ActionTab = 'block' | 'series' | 'unblock';

const selectedStart = ref<string | null>(null);
const selectedEnd = ref<string | null>(null);
const activeTab = ref<ActionTab>('block');

const weekdays = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

const weekdayLabels = new Map(weekdays.map((day) => [day.value, day.label]));

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

defineOptions({
    layout: (props: {
        listing: { name: string };
        urls: {
            index: string;
            manage: string;
            edit: string;
            bookings: string;
            customers: string;
            availability: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Disponibilidad' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Reservaciones', href: props.urls.bookings },
            { title: 'Clientes', href: props.urls.customers },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Fotos y datos', href: props.urls.edit },
        ],
    }),
});

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
});

const seriesForm = useForm({
    reason: '',
    recurrence: 'weekly',
    weekdays: [1],
    starts_on: '',
    ends_on: '',
});

const unblockForm = useForm({
    start_date: '',
    end_date: '',
});

const blockedCount = computed(
    () =>
        props.blocked_dates.filter((date) => date.status === 'blocked').length,
);

const bookedCount = computed(
    () => props.blocked_dates.filter((date) => date.status === 'booked').length,
);

const heldCount = computed(
    () => props.blocked_dates.filter((date) => date.status === 'held').length,
);

function formatDate(value: string): string {
    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return dateFormatter.format(new Date(year, month - 1, day));
}

function selectDate(date: string): void {
    if (
        selectedStart.value === null ||
        (selectedStart.value !== null && selectedEnd.value !== null)
    ) {
        selectedStart.value = date;
        selectedEnd.value = date;
    } else if (date < selectedStart.value) {
        selectedEnd.value = selectedStart.value;
        selectedStart.value = date;
    } else {
        selectedEnd.value = date;
    }

    form.start_date = selectedStart.value;
    form.end_date = selectedEnd.value;
    seriesForm.starts_on = selectedStart.value;
    seriesForm.ends_on = selectedEnd.value;
    unblockForm.start_date = selectedStart.value;
    unblockForm.end_date = selectedEnd.value;
    form.clearErrors();
    seriesForm.clearErrors();
    unblockForm.clearErrors();
}

function clearSelection(): void {
    selectedStart.value = null;
    selectedEnd.value = null;
    form.start_date = '';
    form.end_date = '';
    seriesForm.starts_on = '';
    seriesForm.ends_on = '';
    unblockForm.start_date = '';
    unblockForm.end_date = '';
    form.clearErrors();
    seriesForm.clearErrors();
    unblockForm.clearErrors();
}

function toggleWeekday(day: number): void {
    seriesForm.weekdays = seriesForm.weekdays.includes(day)
        ? seriesForm.weekdays.filter((value) => value !== day)
        : [...seriesForm.weekdays, day];
}

function submit(): void {
    form.post(props.urls.block_range, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedStart.value = null;
            selectedEnd.value = null;
        },
    });
}

const selectionActionLabel = computed(() => {
    if (activeTab.value === 'series') {
        return 'Crear serie';
    }

    if (activeTab.value === 'unblock') {
        return 'Liberar fechas';
    }

    return 'Bloquear fechas';
});

const selectionActionIcon = computed(() => {
    if (activeTab.value === 'series') {
        return Repeat;
    }

    if (activeTab.value === 'unblock') {
        return Unlock;
    }

    return Lock;
});

function submitSelection(): void {
    if (!selectedStart.value) {
        return;
    }

    if (activeTab.value === 'series') {
        submitSeries();

        return;
    }

    if (activeTab.value === 'unblock') {
        unblockRange();

        return;
    }

    submit();
}

function submitSeries(): void {
    seriesForm.post(props.urls.series_store, {
        preserveScroll: true,
        onSuccess: () => {
            seriesForm.reset();
            selectedStart.value = null;
            selectedEnd.value = null;
        },
    });
}

function deleteSeries(series: BlockSeries): void {
    if (!window.confirm('¿Eliminar esta serie y liberar sus fechas futuras?')) {
        return;
    }

    router.delete(series.destroy_url, { preserveScroll: true });
}

function unblockRange(): void {
    unblockForm.delete(props.urls.unblock_range, {
        preserveScroll: true,
        onSuccess: () => {
            unblockForm.reset();
            selectedStart.value = null;
            selectedEnd.value = null;
        },
    });
}

function unblock(date: string): void {
    if (!window.confirm('¿Liberar esta fecha?')) {
        return;
    }

    router.delete(props.urls.unblock.replace('__DATE__', date), {
        preserveScroll: true,
    });
}

function seriesWeekdays(series: BlockSeries): string {
    if (series.recurrence === 'monthly') {
        return '';
    }

    return series.weekdays
        .map((day) => weekdayLabels.get(day))
        .filter((label): label is string => Boolean(label))
        .join(' · ');
}

function dateStatusLabel(status: BlockedDate['status']): string {
    return {
        blocked: 'Bloqueada',
        held: 'Apartada',
        booked: 'Reservada',
    }[status];
}

function dateStatusVariant(
    status: BlockedDate['status'],
): 'default' | 'secondary' | 'destructive' | 'outline' {
    const variants: Record<
        BlockedDate['status'],
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        blocked: 'outline',
        held: 'secondary',
        booked: 'destructive',
    };

    return variants[status];
}
</script>

<template>
    <Head :title="`Disponibilidad · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <CalendarDays class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">Disponibilidad</h1>
                        <Badge variant="outline">
                            {{ listing.status_label }}
                        </Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        {{ listing.name }} · Zona horaria:
                        {{ listing.timezone }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Bloquea fechas para mantenimiento o uso interno y libera
                        rangos cuando vuelvan a estar disponibles.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.manage">Volver al resumen</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="urls.public_listing"
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
                    <CalendarOff class="size-4" />
                    Bloqueadas
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ blockedCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Fechas bloqueadas manualmente
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <CalendarCheck class="size-4" />
                    Reservadas
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ bookedCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Ocupadas por reservas confirmadas
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Clock class="size-4" />
                    Apartadas
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ heldCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Retenidas temporalmente
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Repeat class="size-4" />
                    Series recurrentes
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ series.length }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Bloqueos repetidos automáticamente
                </p>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            <section class="space-y-4">
                <div
                    v-if="selectedStart"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <CalendarDays class="size-4" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">
                                Rango seleccionado
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(selectedStart) }}
                                <span v-if="selectedEnd !== selectedStart">
                                    al {{ formatDate(selectedEnd ?? '') }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="clearSelection"
                        >
                            <X class="size-4" />
                            Limpiar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="
                                form.processing ||
                                seriesForm.processing ||
                                unblockForm.processing
                            "
                            @click="submitSelection"
                        >
                            <component
                                :is="selectionActionIcon"
                                class="size-4"
                            />
                            {{ selectionActionLabel }}
                        </Button>
                    </div>
                </div>
                <div
                    v-else
                    class="flex items-center gap-3 rounded-xl border border-dashed bg-muted/20 p-4 text-sm text-muted-foreground"
                >
                    <CalendarDays class="size-4 shrink-0" />
                    Selecciona un día o un rango en el calendario para
                    bloquearlo.
                </div>

                <AvailabilityCalendar
                    :blocked-dates="blocked_dates"
                    :months="3"
                    selectable
                    disable-past
                    :selected-start="selectedStart"
                    :selected-end="selectedEnd"
                    @select="selectDate"
                />
            </section>

            <aside class="space-y-4">
                <section class="overflow-hidden rounded-xl border bg-card">
                    <div
                        class="grid grid-cols-3 gap-1 border-b bg-muted/30 p-1"
                        role="tablist"
                        aria-label="Acciones de disponibilidad"
                    >
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === 'block'"
                            class="flex items-center justify-center gap-1.5 rounded-md px-2 py-2 text-xs font-medium transition-colors"
                            :class="
                                activeTab === 'block'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="activeTab = 'block'"
                        >
                            <Lock class="size-3.5" />
                            Bloquear
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === 'series'"
                            class="flex items-center justify-center gap-1.5 rounded-md px-2 py-2 text-xs font-medium transition-colors"
                            :class="
                                activeTab === 'series'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="activeTab = 'series'"
                        >
                            <Repeat class="size-3.5" />
                            Recurrente
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === 'unblock'"
                            class="flex items-center justify-center gap-1.5 rounded-md px-2 py-2 text-xs font-medium transition-colors"
                            :class="
                                activeTab === 'unblock'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="activeTab = 'unblock'"
                        >
                            <Unlock class="size-3.5" />
                            Liberar
                        </button>
                    </div>

                    <div class="p-4">
                        <form
                            v-if="activeTab === 'block'"
                            class="grid gap-3"
                            @submit.prevent="submit"
                        >
                            <div>
                                <h2 class="font-semibold">Bloquear fechas</h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Selecciona un día o rango en el calendario o
                                    captura las fechas manualmente.
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="start_date">Desde</Label>
                                    <Input
                                        id="start_date"
                                        v-model="form.start_date"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.start_date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="end_date">Hasta</Label>
                                    <Input
                                        id="end_date"
                                        v-model="form.end_date"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.end_date"
                                    />
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Se incluyen ambas fechas. Para un solo día, usa
                                la misma fecha en ambos campos.
                            </p>
                            <div class="grid gap-2">
                                <Label for="reason">Motivo</Label>
                                <Input
                                    id="reason"
                                    v-model="form.reason"
                                    placeholder="Mantenimiento"
                                />
                                <InputError :message="form.errors.reason" />
                            </div>
                            <Button type="submit" :disabled="form.processing">
                                <Lock class="size-4" />
                                Bloquear rango
                            </Button>
                        </form>

                        <form
                            v-else-if="activeTab === 'series'"
                            class="grid gap-3"
                            @submit.prevent="submitSeries"
                        >
                            <div>
                                <h2 class="font-semibold">
                                    Bloqueo recurrente
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Repite un bloqueo por día, semana o mes
                                    dentro de un periodo.
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="series-recurrence">
                                    Frecuencia
                                </Label>
                                <select
                                    id="series-recurrence"
                                    v-model="seriesForm.recurrence"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="recurrence in recurrences"
                                        :key="recurrence.value"
                                        :value="recurrence.value"
                                    >
                                        {{ recurrence.label }}
                                    </option>
                                </select>
                                <InputError
                                    :message="seriesForm.errors.recurrence"
                                />
                            </div>

                            <div
                                v-if="seriesForm.recurrence !== 'monthly'"
                                class="grid gap-2"
                            >
                                <Label>Días de la semana</Label>
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="day in weekdays"
                                        :key="day.value"
                                        type="button"
                                        class="min-w-9 rounded-md border px-2 py-1.5 text-xs font-medium transition-colors"
                                        :class="
                                            seriesForm.weekdays.includes(
                                                day.value,
                                            )
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'text-muted-foreground hover:bg-accent'
                                        "
                                        :aria-pressed="
                                            seriesForm.weekdays.includes(
                                                day.value,
                                            )
                                        "
                                        @click="toggleWeekday(day.value)"
                                    >
                                        {{ day.label }}
                                    </button>
                                </div>
                                <InputError
                                    :message="seriesForm.errors.weekdays"
                                />
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="series-starts-on">
                                        Desde
                                    </Label>
                                    <Input
                                        id="series-starts-on"
                                        v-model="seriesForm.starts_on"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="seriesForm.errors.starts_on"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="series-ends-on">Hasta</Label>
                                    <Input
                                        id="series-ends-on"
                                        v-model="seriesForm.ends_on"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="seriesForm.errors.ends_on"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="series-reason">Motivo</Label>
                                <Input
                                    id="series-reason"
                                    v-model="seriesForm.reason"
                                    placeholder="Limpieza semanal"
                                />
                                <InputError
                                    :message="seriesForm.errors.reason"
                                />
                            </div>

                            <p class="text-xs text-muted-foreground">
                                Máximo {{ horizon_days }} días. Las fechas ya
                                reservadas o apartadas se omiten.
                            </p>

                            <Button
                                type="submit"
                                :disabled="seriesForm.processing"
                            >
                                <Repeat class="size-4" />
                                Crear serie
                            </Button>
                        </form>

                        <form
                            v-else
                            class="grid gap-3"
                            @submit.prevent="unblockRange"
                        >
                            <div>
                                <h2 class="font-semibold">Liberar rango</h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Vuelve a poner disponibles las fechas
                                    bloqueadas manualmente.
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="unblock_start_date">
                                        Desde
                                    </Label>
                                    <Input
                                        id="unblock_start_date"
                                        v-model="unblockForm.start_date"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="unblockForm.errors.start_date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="unblock_end_date">
                                        Hasta
                                    </Label>
                                    <Input
                                        id="unblock_end_date"
                                        v-model="unblockForm.end_date"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        :message="unblockForm.errors.end_date"
                                    />
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Solo libera fechas bloqueadas manualmente. Las
                                reservadas o apartadas se conservan.
                            </p>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="unblockForm.processing"
                            >
                                <Unlock class="size-4" />
                                Liberar rango
                            </Button>
                        </form>
                    </div>
                </section>
            </aside>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border bg-card">
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                >
                    <div>
                        <h2 class="font-semibold">Series recurrentes</h2>
                        <p class="text-xs text-muted-foreground">
                            Bloqueos que se repiten automáticamente.
                        </p>
                    </div>
                    <Badge variant="secondary">
                        {{ series.length }}
                        {{ series.length === 1 ? 'serie' : 'series' }}
                    </Badge>
                </div>
                <div
                    v-if="series.length > 0"
                    class="max-h-80 space-y-2 overflow-y-auto p-4"
                >
                    <div
                        v-for="serie in series"
                        :key="serie.id"
                        class="rounded-lg border p-3 text-sm"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ serie.reason ?? 'Bloqueo recurrente' }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ serie.recurrence_label }} ·
                                    {{ formatDate(serie.starts_on) }} al
                                    {{ formatDate(serie.ends_on) }} ·
                                    {{ serie.unavailabilities_count }}
                                    {{
                                        serie.unavailabilities_count === 1
                                            ? 'fecha'
                                            : 'fechas'
                                    }}
                                </p>
                                <p
                                    v-if="seriesWeekdays(serie)"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ seriesWeekdays(serie) }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                @click="deleteSeries(serie)"
                            >
                                <Trash2 class="size-4" />
                                Eliminar
                            </Button>
                        </div>
                    </div>
                </div>
                <p v-else class="p-6 text-sm text-muted-foreground">
                    No hay series recurrentes.
                </p>
            </section>

            <section class="overflow-hidden rounded-xl border bg-card">
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                >
                    <div>
                        <h2 class="font-semibold">Fechas no disponibles</h2>
                        <p class="text-xs text-muted-foreground">
                            Bloqueos manuales, apartados y reservas.
                        </p>
                    </div>
                    <Badge variant="secondary">
                        {{ blocked_dates.length }}
                        {{ blocked_dates.length === 1 ? 'fecha' : 'fechas' }}
                    </Badge>
                </div>
                <div
                    v-if="blocked_dates.length > 0"
                    class="max-h-80 space-y-2 overflow-y-auto p-4"
                >
                    <div
                        v-for="date in blocked_dates"
                        :key="date.date"
                        class="flex items-start justify-between gap-3 rounded-lg border p-3 text-sm"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">
                                    {{ formatDate(date.date) }}
                                </p>
                                <Badge
                                    :variant="dateStatusVariant(date.status)"
                                >
                                    {{ dateStatusLabel(date.status) }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ date.label }}
                                <span v-if="date.reason">
                                    · {{ date.reason }}</span
                                >
                            </p>
                        </div>
                        <Button
                            v-if="date.status === 'blocked'"
                            size="sm"
                            variant="outline"
                            @click="unblock(date.date)"
                        >
                            <Unlock class="size-4" />
                            Liberar
                        </Button>
                    </div>
                </div>
                <p v-else class="p-6 text-sm text-muted-foreground">
                    Todas las fechas están disponibles.
                </p>
            </section>
        </div>
    </div>
</template>
