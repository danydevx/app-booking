<script setup lang="ts">
import { computed } from 'vue';
import { featureIcons, type FeatureIconName } from './featureIcon';

const props = withDefaults(
    defineProps<{
        icon: FeatureIconName;
        text?: string;
        variant?: 'solid' | 'soft';
    }>(),
    {
        variant: 'solid',
    },
);

const icon = computed(() => featureIcons[props.icon]);

const circleClasses = computed(() =>
    props.variant === 'soft'
        ? 'bg-[color:var(--home-primary)]/10 text-[color:var(--home-primary)]'
        : 'bg-[color:var(--home-primary)] text-white',
);
</script>

<template>
    <span class="flex min-w-0 items-center gap-3">
        <span
            class="flex size-9 shrink-0 items-center justify-center rounded-full"
            :class="circleClasses"
            aria-hidden="true"
        >
            <component
                :is="icon"
                :size="20"
                :stroke-width="1.8"
                focusable="false"
            />
        </span>
        <span class="min-w-0"
            ><slot>{{ text }}</slot></span
        >
    </span>
</template>
