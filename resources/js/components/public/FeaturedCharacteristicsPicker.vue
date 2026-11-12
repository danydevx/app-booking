<script setup lang="ts">
import { Check, Home, Sparkles, Sprout, Trees, Waves } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';

type FeaturedCharacteristicOption = {
    key: string;
    name: string;
    description: string | null;
    icon: string | null;
};

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        options: FeaturedCharacteristicOption[];
        legend?: string;
        showLabels?: boolean;
        showDescriptions?: boolean;
        max?: number | null;
    }>(),
    {
        showLabels: true,
        showDescriptions: true,
        max: null,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const iconMap: Record<string, Component> = {
    trees: Trees,
    waves: Waves,
    home: Home,
    sprout: Sprout,
    sparkles: Sparkles,
};

const selectedKeys = computed(() => new Set(props.modelValue));
const atLimit = computed(
    () => props.max !== null && selectedKeys.value.size >= props.max,
);

function iconFor(name: string | null): Component {
    return iconMap[name ?? ''] ?? Sparkles;
}

function isDisabled(key: string): boolean {
    return atLimit.value && !selectedKeys.value.has(key);
}

function toggle(key: string): void {
    if (isDisabled(key)) {
        return;
    }

    const next = selectedKeys.value.has(key)
        ? props.modelValue.filter((value) => value !== key)
        : [...props.modelValue, key];

    emit('update:modelValue', next);
}
</script>

<template>
    <fieldset class="min-w-0">
        <legend v-if="legend" class="text-sm font-semibold">
            {{ legend }}
        </legend>
        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <button
                v-for="option in options"
                :key="option.key"
                type="button"
                role="checkbox"
                :aria-checked="selectedKeys.has(option.key)"
                :aria-label="`${option.name}: ${option.description ?? ''}`"
                :title="option.name"
                :disabled="isDisabled(option.key)"
                class="group flex min-w-0 items-start gap-3 rounded-xl border p-3 text-left transition outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                :class="
                    selectedKeys.has(option.key)
                        ? 'border-primary bg-primary/5 shadow-sm'
                        : 'border-border bg-card hover:border-primary/40'
                "
                @click="toggle(option.key)"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors"
                    :class="
                        selectedKeys.has(option.key)
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground group-hover:text-foreground'
                    "
                >
                    <component :is="iconFor(option.icon)" class="size-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-start justify-between gap-2">
                        <span v-if="showLabels" class="text-sm font-semibold">
                            {{ option.name }}
                        </span>
                        <span
                            v-if="selectedKeys.has(option.key)"
                            class="ml-auto flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
                        >
                            <Check class="size-3" />
                        </span>
                    </span>
                    <span
                        v-if="showDescriptions && option.description"
                        class="mt-0.5 block text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ option.description }}
                    </span>
                </span>
            </button>
        </div>
    </fieldset>
</template>
