<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
        bookings: string;
        block_range: string;
        unblock: string;
        unblock_range: string;
        series_store: string;
        public_listing: string;
    };
}>();

const selectedStart = ref<string | null>(null);
const selectedEnd = ref<string | null>(null);
const recurring = ref(false);

const weekdays = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Solicitudes',
                href: '/',
            },
            {
                title: 'Disponibilidad',
            },
        ],
    },
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
    seriesForm.ends_on = selectedStart.value;
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

function submitSeries(): void {
    seriesForm.post(props.urls.series_store, {
        preserveScroll: true,
        onSuccess: () => {
            seriesForm.reset();
            recurring.value = false;
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

const unblockForm = useForm({
    start_date: '',
    end_date: '',
});

function unblockRange(): void {
    unblockForm.delete(props.urls.unblock_range, {
        preserveScroll: true,
        onSuccess: () => unblockForm.reset(),
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
</script>

<template>
    <Head :title="`Disponibilidad · ${listing.name}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ listing.name }}</h1>
                    <Badge variant="secondary">{{
                        listing.status_label
                    }}</Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Zona horaria: {{ listing.timezone }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
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
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <AvailabilityCalendar
                :blocked-dates="blocked_dates"
                :months="3"
                selectable
                :selected-start="selectedStart"
                :selected-end="selectedEnd"
                @select="selectDate"
            />

            <aside class="space-y-4">
                <form
                    class="rounded-xl border bg-card p-4"
                    @submit.prevent="submit"
                >
                    <h2 class="mb-3 font-semibold">Bloquear fechas</h2>
                    <p class="mb-3 text-xs text-muted-foreground">
                        Selecciona un día o un rango directamente en el
                        calendario.
                    </p>
                    <div class="grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="start_date">Desde</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    required
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
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Se incluyen ambas fechas. Para un solo día, usa la
                            misma fecha en ambos campos.
                        </p>
                        <p
                            v-if="selectedStart"
                            class="text-xs font-medium text-primary"
                        >
                            Seleccionado: {{ selectedStart }}
                            <span v-if="selectedEnd !== selectedStart">
                                al {{ selectedEnd }}
                            </span>
                        </p>
                        <div class="grid gap-2">
                            <Label for="reason">Motivo</Label>
                            <Input
                                id="reason"
                                v-model="form.reason"
                                placeholder="Mantenimiento"
                            />
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            Bloquear rango
                        </Button>
                    </div>
                </form>

                <div class="rounded-xl border bg-card p-4">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="recurring"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        <span class="text-sm font-semibold">
                            Bloqueo recurrente
                        </span>
                    </label>

                    <form
                        v-if="recurring"
                        class="mt-4 grid gap-3"
                        @submit.prevent="submitSeries"
                    >
                        <div class="grid gap-2">
                            <Label for="series-recurrence">Frecuencia</Label>
                            <select
                                id="series-recurrence"
                                v-model="seriesForm.recurrence"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option
                                    v-for="recurrence in recurrences"
                                    :key="recurrence.value"
                                    :value="recurrence.value"
                                >
                                    {{ recurrence.label }}
                                </option>
                            </select>
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
                                    class="rounded-md border px-2 py-1 text-xs"
                                    :class="
                                        seriesForm.weekdays.includes(day.value)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    @click="toggleWeekday(day.value)"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                            <InputError :message="seriesForm.errors.weekdays" />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="series-starts-on">Desde</Label>
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
                            <InputError :message="seriesForm.errors.reason" />
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Máximo {{ horizon_days }} días. Las fechas ya
                            reservadas o apartadas se omiten.
                        </p>

                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="seriesForm.processing"
                        >
                            Crear serie
                        </Button>
                    </form>
                </div>

                <form
                    class="rounded-xl border bg-card p-4"
                    @submit.prevent="unblockRange"
                >
                    <h2 class="mb-3 font-semibold">Liberar rango</h2>
                    <div class="grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="unblock_start_date">Desde</Label>
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
                                <Label for="unblock_end_date">Hasta</Label>
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
                            Liberar rango
                        </Button>
                    </div>
                </form>

                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-3 font-semibold">Series recurrentes</h2>
                    <div v-if="series.length > 0" class="space-y-2">
                        <div
                            v-for="serie in series"
                            :key="serie.id"
                            class="rounded-md border px-3 py-2 text-sm"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-2"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{
                                            serie.reason ?? 'Bloqueo recurrente'
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ serie.recurrence_label }} ·
                                        {{ serie.starts_on }} al
                                        {{ serie.ends_on }} ·
                                        {{ serie.unavailabilities_count }}
                                        fechas
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="deleteSeries(serie)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No hay series recurrentes.
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-3 font-semibold">Fechas no disponibles</h2>
                    <div v-if="blocked_dates.length > 0" class="space-y-2">
                        <div
                            v-for="date in blocked_dates"
                            :key="date.date"
                            class="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                        >
                            <div>
                                <p class="font-medium">{{ date.date }}</p>
                                <p class="text-xs text-muted-foreground">
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
                                Liberar
                            </Button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Todas las fechas están disponibles.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
