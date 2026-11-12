<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    current: number;
    steps: Array<{ label: string }>;
}>();

const currentLabel = computed(
    () => props.steps[props.current - 1]?.label ?? '',
);

const progress = computed(() => {
    if (props.steps.length <= 1) {
        return 100;
    }

    return ((props.current - 1) / (props.steps.length - 1)) * 100;
});
</script>

<template>
    <div
        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-4 sm:p-5"
    >
        <div class="flex items-center justify-between gap-3">
            <p
                class="text-xs font-bold tracking-[0.12em] text-[color:var(--home-muted)] uppercase"
            >
                Paso {{ current }} de {{ steps.length }}
            </p>
            <p class="text-sm font-semibold text-[color:var(--home-dark)]">
                {{ currentLabel }}
            </p>
        </div>

        <div
            class="mt-3 h-1.5 overflow-hidden rounded-full bg-[color:var(--home-bg-alt)]"
            role="progressbar"
            aria-label="Progreso de la búsqueda guiada"
            aria-valuemin="1"
            :aria-valuemax="steps.length"
            :aria-valuenow="current"
        >
            <div
                class="h-full rounded-full bg-[color:var(--home-primary)] transition-[width] duration-300 motion-reduce:transition-none"
                :style="{ width: `${progress}%` }"
            />
        </div>

        <ol class="mt-3 hidden items-center justify-between gap-2 sm:flex">
            <li
                v-for="(step, index) in steps"
                :key="step.label"
                class="flex items-center gap-2 text-xs font-semibold"
                :class="
                    index + 1 === current
                        ? 'text-[color:var(--home-primary-deep)]'
                        : 'text-[color:var(--home-muted)]'
                "
                :aria-current="index + 1 === current ? 'step' : undefined"
            >
                <span
                    class="flex size-5 items-center justify-center rounded-full text-[0.6875rem]"
                    :class="
                        index + 1 === current
                            ? 'bg-[color:var(--home-primary)] text-white'
                            : index + 1 < current
                              ? 'bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]'
                              : 'bg-[color:var(--home-bg-alt)] text-[color:var(--home-muted)]'
                    "
                >
                    {{ index + 1 }}
                </span>
                {{ step.label }}
            </li>
        </ol>
    </div>
</template>
