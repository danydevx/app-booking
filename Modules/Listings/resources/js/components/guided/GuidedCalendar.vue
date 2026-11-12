<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    modelValue: string[];
    minDate: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const weekDays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const initial = new Date(`${props.minDate}T12:00:00`);
const viewYear = ref(initial.getFullYear());
const viewMonth = ref(initial.getMonth());

const selected = computed(() => new Set(props.modelValue));

const monthLabel = computed(() =>
    new Intl.DateTimeFormat('es-MX', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(viewYear.value, viewMonth.value, 1)),
);

const canGoPrevious = computed(() => {
    const min = new Date(`${props.minDate}T12:00:00`);

    return (
        viewYear.value > min.getFullYear() ||
        (viewYear.value === min.getFullYear() &&
            viewMonth.value > min.getMonth())
    );
});

const cells = computed(() => {
    const firstDay = new Date(viewYear.value, viewMonth.value, 1);
    const daysInMonth = new Date(
        viewYear.value,
        viewMonth.value + 1,
        0,
    ).getDate();
    const leading = (firstDay.getDay() + 6) % 7;
    const today = props.minDate;

    return [
        ...Array.from({ length: leading }, () => null),
        ...Array.from({ length: daysInMonth }, (_, index) => {
            const day = index + 1;
            const date = `${viewYear.value.toString().padStart(4, '0')}-${(
                viewMonth.value + 1
            )
                .toString()
                .padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

            return {
                date,
                day,
                disabled: date < today,
                selected: selected.value.has(date),
                isToday: date === today,
            };
        }),
    ];
});

function changeMonth(delta: number): void {
    const next = new Date(viewYear.value, viewMonth.value + delta, 1);
    viewYear.value = next.getFullYear();
    viewMonth.value = next.getMonth();
}

function toggle(date: string): void {
    const next = selected.value.has(date)
        ? props.modelValue.filter((value) => value !== date)
        : [...props.modelValue, date];

    emit('update:modelValue', [...new Set(next)].sort());
}

function dateLabel(date: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${date}T12:00:00`));
}
</script>

<template>
    <div
        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-4 sm:p-6"
    >
        <div class="flex items-center justify-between gap-3">
            <button
                type="button"
                class="home-btn home-btn--outline home-btn--sm size-9 p-0"
                :disabled="!canGoPrevious"
                aria-label="Mes anterior"
                @click="changeMonth(-1)"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
            </button>
            <p class="text-base font-bold capitalize">
                {{ monthLabel }}
            </p>
            <button
                type="button"
                class="home-btn home-btn--outline home-btn--sm size-9 p-0"
                aria-label="Mes siguiente"
                @click="changeMonth(1)"
            >
                <ChevronRight class="size-4" aria-hidden="true" />
            </button>
        </div>

        <div class="mt-4 grid grid-cols-7 gap-1 text-center">
            <span
                v-for="day in weekDays"
                :key="day"
                class="py-1 text-[0.6875rem] font-bold tracking-wide text-[color:var(--home-muted)] uppercase"
            >
                {{ day }}
            </span>

            <template v-for="(cell, index) in cells" :key="index">
                <span v-if="cell === null" aria-hidden="true" />
                <button
                    v-else
                    type="button"
                    class="relative flex aspect-square items-center justify-center rounded-full text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--home-primary)] disabled:cursor-not-allowed disabled:text-[color:var(--home-muted)]/40"
                    :class="
                        cell.selected
                            ? 'bg-[color:var(--home-primary)] text-white'
                            : cell.isToday
                              ? 'border border-[color:var(--home-primary)] text-[color:var(--home-primary-deep)] hover:bg-[color:var(--home-soft)]'
                              : 'text-[color:var(--home-dark)] hover:bg-[color:var(--home-bg-alt)]'
                    "
                    :disabled="cell.disabled"
                    :aria-pressed="cell.selected"
                    :aria-label="dateLabel(cell.date)"
                    @click="toggle(cell.date)"
                >
                    {{ cell.day }}
                </button>
            </template>
        </div>
    </div>
</template>
