<script setup lang="ts">
import { computed } from 'vue';
import type { BlockedDate } from '../types';

const props = withDefaults(
    defineProps<{
        blockedDates: BlockedDate[];
        months?: number;
        selectable?: boolean;
        disablePast?: boolean;
        selectedStart?: string | null;
        selectedEnd?: string | null;
        variant?: 'admin' | 'public';
    }>(),
    {
        months: 2,
        selectable: false,
        disablePast: false,
        selectedStart: null,
        selectedEnd: null,
        variant: 'admin',
    },
);

const publicTheme = computed(() => props.variant === 'public');

const emit = defineEmits<{
    select: [date: string];
}>();

const weekdays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const today = (() => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
})();

const dateFormatter = new Intl.DateTimeFormat('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

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

function isToday(date: string): boolean {
    return date === today;
}

function isDisabled(date: string): boolean {
    return props.selectable && props.disablePast && date < today;
}

function dateAriaLabel(date: string): string {
    const blocked = blockedMap.value.get(date);

    if (!blocked) {
        return dateFormatter.format(new Date(`${date}T12:00:00`));
    }

    return `${dateFormatter.format(new Date(`${date}T12:00:00`))}: ${blocked.label}`;
}

function isSelected(date: string): boolean {
    if (props.selectedStart === null) {
        return false;
    }

    const end = props.selectedEnd ?? props.selectedStart;

    return date >= props.selectedStart && date <= end;
}

function dateClass(date: string): string {
    if (props.selectable && isSelected(date)) {
        return publicTheme.value
            ? 'border-[color:var(--home-primary)] bg-[color:var(--home-primary)] text-white'
            : 'border-primary bg-primary text-primary-foreground';
    }

    const blocked = blockedMap.value.get(date);

    if (!blocked) {
        return publicTheme.value
            ? 'bg-white text-[color:var(--home-dark)]'
            : 'bg-background text-foreground';
    }

    if (blocked.status === 'booked') {
        return publicTheme.value
            ? 'bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]'
            : 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200';
    }

    if (blocked.status === 'held') {
        return publicTheme.value
            ? 'bg-[#FEF3C7] text-[#92400E]'
            : 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200';
    }

    return publicTheme.value
        ? 'bg-[color:var(--home-bg-alt)] text-[color:var(--home-muted)]'
        : 'bg-muted text-muted-foreground';
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
                :class="
                    publicTheme
                        ? 'rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-4'
                        : 'rounded-xl border bg-card p-4'
                "
            >
                <h3
                    class="mb-3 capitalize"
                    :class="
                        publicTheme
                            ? 'text-sm font-bold'
                            : 'text-sm font-semibold'
                    "
                >
                    {{ month.label }}
                </h3>

                <div
                    class="grid grid-cols-7 gap-1 text-center"
                    :class="publicTheme ? 'text-sm' : 'text-xs'"
                >
                    <span
                        v-for="(weekday, index) in weekdays"
                        :key="`${month.key}-weekday-${index}`"
                        class="py-1"
                        :class="
                            publicTheme
                                ? 'font-semibold text-[color:var(--home-muted)]'
                                : 'font-medium text-muted-foreground'
                        "
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
                            class="flex aspect-square items-center justify-center border border-transparent transition-colors"
                            :class="[
                                publicTheme
                                    ? 'rounded-[0.5rem] focus-visible:ring-2 focus-visible:ring-[color:var(--home-primary)]/40 enabled:hover:bg-[color:var(--home-bg-alt)]'
                                    : 'rounded-md text-sm focus-visible:ring-2 focus-visible:ring-ring/50 enabled:hover:bg-accent',
                                dateClass(date),
                                isToday(date) && !isSelected(date)
                                    ? publicTheme
                                        ? 'ring-1 ring-[color:var(--home-primary)]/50'
                                        : 'ring-1 ring-primary/50'
                                    : '',
                                isDisabled(date)
                                    ? 'cursor-not-allowed opacity-40'
                                    : '',
                            ]"
                            :disabled="isDisabled(date)"
                            :title="dateTitle(date)"
                            :aria-label="dateAriaLabel(date)"
                            :aria-pressed="isSelected(date)"
                            @click="emit('select', date)"
                        >
                            {{ Number(date.slice(-2)) }}
                        </button>
                        <span
                            v-else
                            class="flex aspect-square items-center justify-center border border-transparent"
                            :class="[
                                publicTheme
                                    ? 'rounded-[0.5rem]'
                                    : 'rounded-md text-sm',
                                dateClass(date),
                            ]"
                            :title="dateTitle(date)"
                        >
                            {{ Number(date.slice(-2)) }}
                        </span>
                    </template>
                </div>
            </section>
        </div>

        <div
            class="flex flex-wrap gap-4 text-xs"
            :class="
                publicTheme
                    ? 'text-[color:var(--home-muted)]'
                    : 'text-muted-foreground'
            "
        >
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm border"
                    :class="
                        publicTheme
                            ? 'border-[color:var(--home-border)] bg-white'
                            : 'bg-background'
                    "
                />
                Disponible
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm"
                    :class="
                        publicTheme
                            ? 'bg-[color:var(--home-bg-alt)]'
                            : 'bg-muted'
                    "
                />
                Bloqueada
            </span>
            <span class="flex items-center gap-2">
                <span class="size-3 rounded-sm bg-[#FEF3C7]" />
                Apartada
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm"
                    :class="
                        publicTheme
                            ? 'bg-[color:var(--home-soft)]'
                            : 'bg-rose-100 dark:bg-rose-950/40'
                    "
                />
                Reservada
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-3 rounded-sm border"
                    :class="
                        publicTheme
                            ? 'border-[color:var(--home-primary)]/50'
                            : 'border-primary/50'
                    "
                />
                Hoy
            </span>
        </div>
    </div>
</template>
