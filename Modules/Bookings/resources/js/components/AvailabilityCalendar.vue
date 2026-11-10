<script setup lang="ts">
import { computed } from 'vue';
import type { BlockedDate } from '../types';

const props = withDefaults(
    defineProps<{
        blockedDates: BlockedDate[];
        months?: number;
        selectable?: boolean;
        selectedStart?: string | null;
        selectedEnd?: string | null;
    }>(),
    {
        months: 2,
        selectable: false,
        selectedStart: null,
        selectedEnd: null,
    },
);

const emit = defineEmits<{
    select: [date: string];
}>();

const weekdays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const blockedMap = computed(
    () => new Map(props.blockedDates.map((date) => [date.date, date])),
);

const monthData = computed(() => {
    const result: Array<{
        key: string;
        label: string;
        cells: Array<string | null>;
    }> = [];
    const today = new Date();
    const formatter = new Intl.DateTimeFormat('es-MX', {
        month: 'long',
        year: 'numeric',
    });

    for (let index = 0; index < props.months; index++) {
        const date = new Date(today.getFullYear(), today.getMonth() + index, 1);
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstWeekday = (date.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells: Array<string | null> = Array(firstWeekday).fill(null);

        for (let day = 1; day <= daysInMonth; day++) {
            cells.push(
                `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
            );
        }

        while (cells.length % 7 !== 0) {
            cells.push(null);
        }

        result.push({
            key: `${year}-${month}`,
            label: formatter.format(date),
            cells,
        });
    }

    return result;
});

function isSelected(date: string): boolean {
    if (props.selectedStart === null) {
        return false;
    }

    const end = props.selectedEnd ?? props.selectedStart;

    return date >= props.selectedStart && date <= end;
}

function dateClass(date: string): string {
    if (props.selectable && isSelected(date)) {
        return 'border-primary bg-primary text-primary-foreground';
    }

    const blocked = blockedMap.value.get(date);

    if (!blocked) {
        return 'bg-background text-foreground';
    }

    if (blocked.status === 'booked') {
        return 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200';
    }

    if (blocked.status === 'held') {
        return 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200';
    }

    return 'bg-muted text-muted-foreground';
}

function dateTitle(date: string): string {
    const blocked = blockedMap.value.get(date);

    return blocked
        ? `${blocked.label}${blocked.reason ? `: ${blocked.reason}` : ''}`
        : '';
}
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <section
                v-for="month in monthData"
                :key="month.key"
                class="rounded-xl border bg-card p-4"
            >
                <h3 class="mb-3 text-sm font-semibold capitalize">
                    {{ month.label }}
                </h3>

                <div class="grid grid-cols-7 gap-1 text-center text-xs">
                    <span
                        v-for="(weekday, index) in weekdays"
                        :key="`${month.key}-weekday-${index}`"
                        class="py-1 font-medium text-muted-foreground"
                    >
                        {{ weekday }}
                    </span>

                    <template
                        v-for="(date, index) in month.cells"
                        :key="`${month.key}-${index}`"
                    >
                        <span v-if="date === null" class="aspect-square" />
                        <button
                            v-else-if="selectable"
                            type="button"
                            class="flex aspect-square items-center justify-center rounded-md border border-transparent text-sm transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/50"
                            :class="dateClass(date)"
                            :title="dateTitle(date)"
                            @click="emit('select', date)"
                        >
                            {{ Number(date.slice(-2)) }}
                        </button>
                        <span
                            v-else
                            class="flex aspect-square items-center justify-center rounded-md border border-transparent text-sm"
                            :class="dateClass(date)"
                            :title="dateTitle(date)"
                        >
                            {{ Number(date.slice(-2)) }}
                        </span>
                    </template>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span class="flex items-center gap-2">
                <span class="size-3 rounded-sm border bg-background" />
                Disponible
            </span>
            <span class="flex items-center gap-2">
                <span class="size-3 rounded-sm bg-muted" />
                Bloqueada
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm bg-amber-100 dark:bg-amber-950/40"
                />
                Apartada
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm bg-rose-100 dark:bg-rose-950/40"
                />
                Reservada
            </span>
        </div>
    </div>
</template>
