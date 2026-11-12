<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, SlidersHorizontal } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import FeatureMultiSelect from '@/components/public/FeatureMultiSelect.vue';
import FeaturedCharacteristicsPicker from '@/components/public/FeaturedCharacteristicsPicker.vue';

type PublicFilters = {
    q?: string | null;
    state?: string | null;
    city?: string | null;
    postal_code?: string | null;
    capacity?: string | number | null;
    service?: string | null;
    features?: string[] | null;
    event?: string | null;
    events?: string[] | null;
    price_min?: string | number | null;
    price_max?: string | number | null;
    lat?: string | number | null;
    lng?: string | number | null;
    radius?: string | number | null;
};

type ServiceOption = {
    value: string;
    label: string;
};

type LocationSuggestion = {
    type: 'city' | 'state' | 'postal_code';
    label: string;
    sublabel: string;
    params: Record<string, string>;
};

const props = defineProps<{
    action: string;
    suggestionsUrl: string;
    filters: PublicFilters;
    services: ServiceOption[];
    eventTypes: Array<{ value: string; label: string }>;
    featuredCharacteristics: Array<{
        key: string;
        name: string;
        description: string | null;
        icon: string | null;
    }>;
    locations: Record<string, { label: string; municipalities: string[] }>;
}>();

const priceOptions = [
    { value: '', label: 'Cualquier precio' },
    { value: '0-5000', label: 'Hasta $5,000' },
    { value: '5000-10000', label: '$5,000 – $10,000' },
    { value: '10000-20000', label: '$10,000 – $20,000' },
    { value: '20000-', label: 'Más de $20,000' },
];

const initialLabel =
    (props.filters.q as string) ||
    (props.filters.postal_code as string) ||
    (props.filters.city as string) ||
    (props.filters.state
        ? (props.locations[props.filters.state]?.label ?? props.filters.state)
        : '');

const hasStructuredLocation = Boolean(
    props.filters.state || props.filters.city || props.filters.postal_code,
);

const structuredParams = computed<Record<string, string>>(() => {
    const params: Record<string, string> = {};

    if (props.filters.state) {
        params.state = String(props.filters.state);
    }

    if (props.filters.city) {
        params.city = String(props.filters.city);
    }

    if (props.filters.postal_code) {
        params.postal_code = String(props.filters.postal_code);
    }

    return params;
});

const query = ref(initialLabel);
const selected = ref<{ label: string; params: Record<string, string> } | null>(
    hasStructuredLocation
        ? { label: initialLabel, params: structuredParams.value }
        : null,
);
const suggestions = ref<LocationSuggestion[]>([]);
const suggestionsOpen = ref(false);
const suggestionsLoading = ref(false);
const activeIndex = ref(-1);
const capacity = ref<string | number>(props.filters.capacity ?? '');
const event = ref<string>((props.filters.event as string) ?? '');
const features = ref<string[]>(featuresFromFilters(props.filters));
const price = ref(priceFromFilters());
const filtersOpen = ref(false);

const panelFilterCount = computed(() => features.value.length);

function featuresFromFilters(filters: PublicFilters): string[] {
    const selected = Array.isArray(filters.features)
        ? filters.features.filter(
              (value): value is string => typeof value === 'string',
          )
        : [];

    if (typeof filters.service === 'string' && filters.service !== '') {
        selected.push(filters.service);
    }

    return [...new Set(selected)].slice(0, 10);
}

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;

function priceFromFilters(): string {
    const min = props.filters.price_min;
    const max = props.filters.price_max;

    if (min === null || min === undefined || min === '') {
        return '';
    }

    const minNumber = Number(min);
    const maxNumber =
        max === null || max === undefined || max === '' ? null : Number(max);

    if (minNumber === 0 && maxNumber === 5000) {
        return '0-5000';
    }

    if (minNumber === 5000 && maxNumber === 10000) {
        return '5000-10000';
    }

    if (minNumber === 10000 && maxNumber === 20000) {
        return '10000-20000';
    }

    if (minNumber === 20000 && maxNumber === null) {
        return '20000-';
    }

    return '';
}

function clearSelection(): void {
    selected.value = null;
}

function clearPanelFilters(): void {
    features.value = [];
}

watch(
    () => props.filters,
    (filters) => {
        features.value = featuresFromFilters(filters);
        event.value = (filters.event as string) ?? '';
        capacity.value = filters.capacity ?? '';
        price.value = priceFromFilters();
    },
    { deep: true },
);

function scheduleSuggestions(): void {
    clearTimeout(debounceTimer);

    const value = query.value.trim();

    if (value.length < 2) {
        suggestions.value = [];
        suggestionsOpen.value = false;

        return;
    }

    debounceTimer = setTimeout(fetchSuggestions, 250);
}

async function fetchSuggestions(): Promise<void> {
    controller?.abort();
    controller = new AbortController();

    const value = query.value.trim();

    suggestionsLoading.value = true;

    try {
        const response = await fetch(
            `${props.suggestionsUrl}?q=${encodeURIComponent(value)}`,
            {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            },
        );

        if (!response.ok) {
            throw new Error('suggestions failed');
        }

        const data = (await response.json()) as {
            suggestions?: LocationSuggestion[];
        };

        suggestions.value = data.suggestions ?? [];
        suggestionsOpen.value = true;
        activeIndex.value = -1;
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            suggestions.value = [];
            suggestionsOpen.value = false;
        }
    } finally {
        suggestionsLoading.value = false;
    }
}

function chooseSuggestion(suggestion: LocationSuggestion): void {
    selected.value = {
        label: suggestion.label,
        params: suggestion.params,
    };
    query.value = suggestion.label;
    suggestionsOpen.value = false;
    activeIndex.value = -1;
}

function onInput(): void {
    clearSelection();
    scheduleSuggestions();
}

function onFocus(): void {
    if (suggestions.value.length > 0) {
        suggestionsOpen.value = true;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (!suggestionsOpen.value || suggestions.value.length === 0) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value =
            activeIndex.value <= 0
                ? suggestions.value.length - 1
                : activeIndex.value - 1;
    } else if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        chooseSuggestion(suggestions.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        suggestionsOpen.value = false;
        activeIndex.value = -1;
    }
}

function submit(): void {
    const params: Record<string, string | number | string[]> = {};

    if (selected.value) {
        Object.assign(params, selected.value.params);
    } else if (query.value.trim() !== '') {
        params.q = query.value.trim();
    }

    if (capacity.value !== '' && capacity.value !== null) {
        params.capacity = Number(capacity.value);
    }

    if (price.value !== '') {
        const [min, max] = price.value.split('-');

        if (min !== '') {
            params.price_min = Number(min);
        }

        if (max !== undefined && max !== '') {
            params.price_max = Number(max);
        }
    }

    if (event.value !== '') {
        params.event = event.value;
    }

    if (features.value.length > 0) {
        params.features = features.value;
    }

    router.get(props.action, params);
}

onBeforeUnmount(() => {
    clearTimeout(debounceTimer);
    controller?.abort();
});
</script>

<template>
    <form
        class="w-full rounded-[1rem] border border-[color:var(--home-border)] bg-white p-3 sm:p-4"
        @submit.prevent="submit"
    >
        <div
            class="grid gap-3 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.6fr)_minmax(0,0.7fr)_auto] lg:items-end"
        >
            <div class="relative">
                <label class="home-field-label" for="geo-location">
                    Ubicación
                </label>
                <input
                    id="geo-location"
                    v-model="query"
                    class="home-input"
                    type="text"
                    placeholder="Ciudad, municipio o código postal"
                    autocomplete="off"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="geo-location-suggestions"
                    :aria-expanded="suggestionsOpen"
                    :aria-activedescendant="
                        activeIndex >= 0
                            ? `geo-suggestion-${activeIndex}`
                            : undefined
                    "
                    @input="onInput"
                    @focus="onFocus"
                    @keydown="onKeydown"
                    @blur="suggestionsOpen = false"
                />

                <ul
                    v-if="suggestionsOpen"
                    id="geo-location-suggestions"
                    class="absolute z-30 mt-2 max-h-80 w-full overflow-auto rounded-xl border border-[color:var(--home-border)] bg-white py-1 shadow-xl"
                    role="listbox"
                >
                    <li
                        v-for="(suggestion, index) in suggestions"
                        :id="`geo-suggestion-${index}`"
                        :key="`${suggestion.type}-${suggestion.label}`"
                        role="option"
                        :aria-selected="index === activeIndex"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-[color:var(--home-bg-alt)]"
                            :class="{
                                'bg-[color:var(--home-bg-alt)]':
                                    index === activeIndex,
                            }"
                            @mousedown.prevent="chooseSuggestion(suggestion)"
                        >
                            <span class="font-semibold">
                                {{ suggestion.label }}
                            </span>
                            <span
                                class="text-xs text-[color:var(--home-muted)]"
                            >
                                {{ suggestion.sublabel }}
                            </span>
                        </button>
                    </li>
                    <li
                        v-if="!suggestionsLoading && suggestions.length === 0"
                        class="px-3 py-3 text-sm text-[color:var(--home-muted)]"
                    >
                        Sin coincidencias. Prueba con otro municipio o código
                        postal, o busca en el mapa.
                    </li>
                </ul>
            </div>

            <div>
                <label class="home-field-label" for="geo-event">
                    Tipo de evento
                </label>
                <select id="geo-event" v-model="event" class="home-select">
                    <option value="">Cualquier evento</option>
                    <option
                        v-for="option in eventTypes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div :class="filtersOpen ? 'block' : 'hidden lg:block'">
                <label class="home-field-label" for="geo-capacity">
                    Invitados
                </label>
                <input
                    id="geo-capacity"
                    v-model="capacity"
                    class="home-input"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    placeholder="Mínimo"
                />
            </div>

            <div :class="filtersOpen ? 'block' : 'hidden lg:block'">
                <label class="home-field-label" for="geo-price">Precio</label>
                <select id="geo-price" v-model="price" class="home-select">
                    <option
                        v-for="option in priceOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="button"
                    class="home-btn home-btn--outline home-btn--sm gap-2"
                    :aria-expanded="filtersOpen"
                    aria-controls="geo-filters-panel"
                    @click="filtersOpen = !filtersOpen"
                >
                    <SlidersHorizontal
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <span>
                        Filtros
                        <span v-if="panelFilterCount > 0">
                            ({{ panelFilterCount }})
                        </span>
                    </span>
                    <ChevronDown
                        class="size-4 shrink-0 transition-transform duration-200 motion-reduce:transition-none"
                        :class="{ 'rotate-180': filtersOpen }"
                        aria-hidden="true"
                    />
                </button>
                <button
                    type="submit"
                    class="home-btn home-btn--primary h-11 flex-1 px-5 lg:flex-none"
                >
                    Buscar
                </button>
            </div>
        </div>

        <div
            id="geo-filters-panel"
            class="grid transition-[grid-template-rows,opacity] duration-300 ease-out motion-reduce:transition-none"
            :class="
                filtersOpen
                    ? 'grid-rows-[1fr] opacity-100'
                    : 'grid-rows-[0fr] opacity-0'
            "
            :inert="filtersOpen ? undefined : true"
            :aria-hidden="!filtersOpen"
        >
            <div
                class="min-h-0"
                :class="filtersOpen ? 'overflow-visible' : 'overflow-hidden'"
            >
                <div
                    class="mt-3 space-y-3 border-t border-[color:var(--home-border)] pt-3"
                >
                    <div class="flex items-center justify-end">
                        <button
                            type="button"
                            class="text-sm font-semibold text-[color:var(--home-primary-deep)] transition-colors hover:text-[color:var(--home-primary-hover)] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="panelFilterCount === 0"
                            @click="clearPanelFilters"
                        >
                            Limpiar
                        </button>
                    </div>

                    <FeaturedCharacteristicsPicker
                        v-if="featuredCharacteristics.length > 0"
                        v-model="features"
                        :options="featuredCharacteristics"
                        :show-descriptions="false"
                        :max="10"
                    />

                    <FeatureMultiSelect
                        v-model="features"
                        :options="services"
                        :max="10"
                    />
                </div>
            </div>
        </div>
    </form>
</template>
