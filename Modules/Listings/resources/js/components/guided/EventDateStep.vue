<script setup lang="ts">
import { CalendarDays, CalendarRange, HelpCircle, X } from '@lucide/vue';
import { computed } from 'vue';
import GuidedCalendar from './GuidedCalendar.vue';
import GuidedSearchStep from './GuidedSearchStep.vue';
import type { GuidedDateMode } from '../../composables/useGuidedSearch';

const props = defineProps<{
    dates: string[];
    dateMode: GuidedDateMode;
    approximateMonth: string | null;
    today: string;
    currentMonth: string;
}>();

const emit = defineEmits<{
    'update:dates': [value: string[]];
    'update:dateMode': [value: GuidedDateMode];
    'update:approximateMonth': [value: string | null];
}>();

const selectedDates = computed(() =>
    [...props.dates].sort((first, second) => first.localeCompare(second)),
);

function dateLabel(date: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${date}T12:00:00`));
}

function removeDate(date: string): void {
    emit(
        'update:dates',
        props.dates.filter((value) => value !== date),
    );
}

function setApproximateMonth(event: Event): void {
    const value = (event.target as HTMLInputElement).value;

    emit('update:approximateMonth', value === '' ? null : value);
}
</script>

<template>
    <GuidedSearchStep
        title="¿Cuándo es tu evento?"
        subtitle="Selecciona una fecha o varias opciones que estés considerando. Puedes elegir días alternativos, no necesariamente consecutivos."
    >
        <div class="grid gap-3 sm:grid-cols-3">
            <button
                type="button"
                class="rounded-[1rem] border p-4 text-left transition-colors"
                :class="
                    dateMode === 'exact'
                        ? 'border-[color:var(--home-primary)] bg-[color:var(--home-soft)]'
                        : 'border-[color:var(--home-border)] bg-white hover:border-[color:var(--home-primary)]/40'
                "
                :aria-pressed="dateMode === 'exact'"
                @click="emit('update:dateMode', 'exact')"
            >
                <CalendarDays
                    class="size-5 text-[color:var(--home-primary-deep)]"
                    aria-hidden="true"
                />
                <span class="mt-3 block text-sm font-bold">
                    Fechas exactas
                </span>
                <span
                    class="mt-1 block text-xs text-[color:var(--home-secondary)]"
                >
                    Elige uno o varios días concretos.
                </span>
            </button>

            <button
                type="button"
                class="rounded-[1rem] border p-4 text-left transition-colors"
                :class="
                    dateMode === 'approximate'
                        ? 'border-[color:var(--home-primary)] bg-[color:var(--home-soft)]'
                        : 'border-[color:var(--home-border)] bg-white hover:border-[color:var(--home-primary)]/40'
                "
                :aria-pressed="dateMode === 'approximate'"
                @click="emit('update:dateMode', 'approximate')"
            >
                <CalendarRange
                    class="size-5 text-[color:var(--home-primary-deep)]"
                    aria-hidden="true"
                />
                <span class="mt-3 block text-sm font-bold">
                    Fecha aproximada
                </span>
                <span
                    class="mt-1 block text-xs text-[color:var(--home-secondary)]"
                >
                    Indica un mes si aún no tienes el día.
                </span>
            </button>

            <button
                type="button"
                class="rounded-[1rem] border p-4 text-left transition-colors"
                :class="
                    dateMode === 'unknown'
                        ? 'border-[color:var(--home-primary)] bg-[color:var(--home-soft)]'
                        : 'border-[color:var(--home-border)] bg-white hover:border-[color:var(--home-primary)]/40'
                "
                :aria-pressed="dateMode === 'unknown'"
                @click="emit('update:dateMode', 'unknown')"
            >
                <HelpCircle
                    class="size-5 text-[color:var(--home-primary-deep)]"
                    aria-hidden="true"
                />
                <span class="mt-3 block text-sm font-bold">
                    Todavía no sé
                </span>
                <span
                    class="mt-1 block text-xs text-[color:var(--home-secondary)]"
                >
                    Continúa y define la fecha después.
                </span>
            </button>
        </div>

        <div v-if="dateMode === 'exact'" class="mt-6">
            <GuidedCalendar
                :model-value="dates"
                :min-date="today"
                @update:model-value="emit('update:dates', $event)"
            />

            <div v-if="selectedDates.length > 0" class="mt-4">
                <p
                    class="text-xs font-bold tracking-[0.1em] text-[color:var(--home-muted)] uppercase"
                >
                    Fechas seleccionadas
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-for="date in selectedDates"
                        :key="date"
                        class="home-chip inline-flex items-center gap-1"
                    >
                        {{ dateLabel(date) }}
                        <button
                            type="button"
                            class="rounded-full p-0.5 transition-colors hover:bg-black/5"
                            :aria-label="`Quitar ${dateLabel(date)}`"
                            @click="removeDate(date)"
                        >
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <div v-else-if="dateMode === 'approximate'" class="mt-6 max-w-md">
            <label class="home-field-label" for="guided-month">
                Mes aproximado
            </label>
            <input
                id="guided-month"
                class="home-control"
                type="month"
                :min="currentMonth"
                :value="approximateMonth ?? ''"
                @input="setApproximateMonth"
            />
            <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                Compartiremos este mes con el propietario para que pueda
                orientarte sobre disponibilidad.
            </p>
        </div>

        <div
            v-else
            class="mt-6 rounded-[1rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] p-4 text-sm text-[color:var(--home-secondary)]"
        >
            No hay problema. Puedes buscar espacios y definir la fecha
            directamente con el propietario.
        </div>

        <p class="mt-5 text-xs leading-relaxed text-[color:var(--home-muted)]">
            Las fechas son una preferencia de búsqueda. La disponibilidad se
            confirma con el propietario.
        </p>
    </GuidedSearchStep>
</template>
