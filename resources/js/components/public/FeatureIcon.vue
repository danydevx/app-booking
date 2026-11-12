<script setup lang="ts">
import { computed } from 'vue';
import {
    featureIcons,
    type FeatureIconName,
    type FeatureIconSize,
    type FeatureIconVariant,
} from './featureIcon';

const props = withDefaults(
    defineProps<{
        name: FeatureIconName;
        size?: FeatureIconSize;
        variant?: FeatureIconVariant;
        label?: string;
        strokeWidth?: number;
    }>(),
    {
        size: 'md',
        variant: 'primary',
        label: undefined,
        strokeWidth: 1.8,
    },
);

const icon = computed(() => featureIcons[props.name]);

const isBare = computed(
    () => props.variant === 'plain' || props.variant === 'muted',
);

const containerClasses = computed(() => {
    const sizeClass =
        props.size === 'sm'
            ? 'size-9 sm:size-10'
            : props.size === 'lg'
              ? 'size-12 sm:size-14'
              : 'size-11 sm:size-12';

    const variantClass =
        props.variant === 'neutral'
            ? 'border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] text-[color:var(--home-dark)]'
            : props.variant === 'plain'
              ? 'text-[color:var(--home-primary)]'
              : props.variant === 'muted'
                ? 'text-[color:var(--home-muted)]'
                : 'bg-[color:var(--home-soft)] text-[color:var(--home-primary)]';

    return [
        'inline-flex shrink-0 items-center justify-center',
        isBare.value ? '' : `rounded-full ${sizeClass}`,
        variantClass,
    ];
});

const iconSize = computed(() =>
    props.size === 'sm' ? 18 : props.size === 'lg' ? 24 : 22,
);
</script>

<template>
    <span
        :class="containerClasses"
        :role="label ? 'img' : undefined"
        :aria-label="label"
        :aria-hidden="label ? undefined : 'true'"
    >
        <component
            :is="icon"
            :size="iconSize"
            :stroke-width="strokeWidth"
            aria-hidden="true"
            focusable="false"
        />
    </span>
</template>
