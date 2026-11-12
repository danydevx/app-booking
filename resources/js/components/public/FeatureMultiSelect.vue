<script setup lang="ts">
import { ChevronDown, Search, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

type FeatureOption = {
    value: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        options: FeatureOption[];
        label?: string;
        max?: number;
    }>(),
    {
        label: 'Características',
        max: 10,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const open = ref(false);
const search = ref('');
const wrapper = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);

const panelId = 'listing-feature-panel';
const searchId = 'listing-feature-search';

const selected = computed(() => new Set(props.modelValue));
const selectedCount = computed(() => props.modelValue.length);
const atLimit = computed(() => selectedCount.value >= props.max);

const normalizedSearch = computed(() => normalize(search.value));

const filteredOptions = computed(() => {
    if (normalizedSearch.value === '') {
        return props.options;
    }

    return props.options.filter((option) =>
        normalize(option.label).includes(normalizedSearch.value),
    );
});

const selectedOptions = computed(() =>
    props.modelValue
        .map((value) => props.options.find((option) => option.value === value))
        .filter((option): option is FeatureOption => option !== undefined),
);

const summary = computed(() => {
    if (selectedCount.value === 0) {
        return 'Cualquier característica';
    }

    return selectedCount.value === 1
        ? '1 característica seleccionada'
        : `${selectedCount.value} características seleccionadas`;
});

function labelFor(value: string): string {
    return (
        props.options.find((option) => option.value === value)?.label ?? value
    );
}

function normalize(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]/g, '');
}

function isDisabled(value: string): boolean {
    return atLimit.value && !selected.value.has(value);
}

function togglePanel(): void {
    open.value = !open.value;

    if (open.value) {
        void nextTick(() => searchInput.value?.focus());
    }
}

function closePanel(focusTrigger = false): void {
    open.value = false;

    if (focusTrigger) {
        void nextTick(() => trigger.value?.focus());
    }
}

function toggle(value: string): void {
    if (selected.value.has(value)) {
        emit(
            'update:modelValue',
            props.modelValue.filter((item) => item !== value),
        );

        return;
    }

    if (atLimit.value) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, value]);
}

function remove(value: string): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((item) => item !== value),
    );
}

function clear(): void {
    emit('update:modelValue', []);
}

function onPointerDown(event: PointerEvent): void {
    if (!open.value) {
        return;
    }

    const target = event.target;

    if (target instanceof Node && wrapper.value?.contains(target)) {
        return;
    }

    closePanel();
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        closePanel(true);
    }
}

onMounted(() => {
    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="wrapper" class="relative">
        <div class="grid gap-1">
            <span class="home-field-label">{{ label }}</span>
            <button
                ref="trigger"
                type="button"
                class="home-control flex items-center justify-between gap-2 text-left"
                :aria-expanded="open"
                aria-haspopup="dialog"
                :aria-controls="panelId"
                @click="togglePanel"
            >
                <span class="min-w-0 truncate text-sm font-semibold">
                    {{ summary }}
                </span>
                <span class="flex shrink-0 items-center gap-2">
                    <span
                        class="text-xs font-semibold text-[color:var(--home-muted)]"
                    >
                        {{ selectedCount }}/{{ max }}
                    </span>
                    <ChevronDown
                        class="size-4 transition-transform duration-200 motion-reduce:transition-none"
                        :class="{ 'rotate-180': open }"
                        aria-hidden="true"
                    />
                </span>
            </button>
        </div>

        <div
            v-if="open"
            :id="panelId"
            role="dialog"
            aria-modal="false"
            :aria-label="label"
            class="absolute left-0 z-40 mt-2 w-[min(22rem,calc(100vw-2rem))] max-w-full rounded-xl border border-[color:var(--home-border)] bg-white p-3 shadow-xl"
        >
            <label class="sr-only" :for="searchId">
                Buscar característica
            </label>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[color:var(--home-muted)]"
                    aria-hidden="true"
                />
                <input
                    :id="searchId"
                    ref="searchInput"
                    v-model="search"
                    type="search"
                    class="home-control pl-9"
                    placeholder="Buscar característica…"
                    autocomplete="off"
                />
            </div>

            <div
                class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-[color:var(--home-muted)]"
            >
                <span role="status" aria-live="polite">
                    {{ selectedCount }}/{{ max }} seleccionadas
                    <template v-if="atLimit"> · máximo alcanzado</template>
                </span>
                <button
                    type="button"
                    class="font-semibold text-[color:var(--home-primary-deep)] transition-colors hover:text-[color:var(--home-primary-hover)] disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="selectedCount === 0"
                    @click="clear"
                >
                    Limpiar selección
                </button>
            </div>

            <p
                v-if="atLimit"
                class="mt-2 rounded-md bg-[color:var(--home-bg-alt)] px-2.5 py-2 text-xs font-medium text-[color:var(--home-secondary)]"
                role="status"
                aria-live="polite"
            >
                Has alcanzado el máximo de {{ max }} características.
            </p>

            <div
                class="mt-2 max-h-64 overflow-y-auto"
                role="group"
                :aria-label="label"
            >
                <label
                    v-for="option in filteredOptions"
                    :key="option.value"
                    class="flex items-center gap-2.5 rounded-md px-2 py-2 text-sm transition-colors hover:bg-[color:var(--home-bg-alt)]"
                    :class="{
                        'cursor-not-allowed opacity-50': isDisabled(
                            option.value,
                        ),
                        'cursor-pointer': !isDisabled(option.value),
                    }"
                >
                    <input
                        type="checkbox"
                        class="home-checkbox shrink-0"
                        :value="option.value"
                        :checked="selected.has(option.value)"
                        :disabled="isDisabled(option.value)"
                        @change="toggle(option.value)"
                    />
                    <span class="min-w-0">
                        {{ option.label }}
                    </span>
                </label>

                <p
                    v-if="filteredOptions.length === 0"
                    class="px-2 py-3 text-sm text-[color:var(--home-muted)]"
                >
                    Sin coincidencias.
                </p>
            </div>
        </div>

        <div v-if="modelValue.length > 0" class="mt-2 flex flex-wrap gap-1.5">
            <span
                v-for="option in selectedOptions"
                :key="option.value"
                class="home-chip inline-flex items-center gap-1"
            >
                {{ option.label }}
                <button
                    type="button"
                    class="rounded-full p-0.5 transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[color:var(--home-primary)]"
                    :aria-label="`Quitar ${option.label}`"
                    @click="remove(option.value)"
                >
                    <X class="size-3" aria-hidden="true" />
                </button>
            </span>
        </div>
    </div>
</template>
