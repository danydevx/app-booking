<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown, SlidersHorizontal } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FeaturedCharacteristicsPicker from '@/components/public/FeaturedCharacteristicsPicker.vue';

type MunicipalityIndex = Record<
    string,
    { label: string; municipalities: string[] }
>;

const props = defineProps<{
    action: string;
    locations: MunicipalityIndex;
    eventTypes: Array<{ value: string; label: string }>;
    featuredCharacteristics: Array<{
        key: string;
        name: string;
        description: string | null;
        icon: string | null;
    }>;
}>();

const page = usePage();
const filtersOpen = ref(false);

function currentParams(): URLSearchParams {
    if (typeof window !== 'undefined') {
        return new URLSearchParams(window.location.search);
    }

    try {
        return new URL(page.url, 'http://localhost').searchParams;
    } catch {
        return new URLSearchParams();
    }
}

function readFeatures(params: URLSearchParams): string[] {
    const features: string[] = [];

    params.forEach((value, key) => {
        if (key === 'features[]' || key.startsWith('features[')) {
            features.push(...value.split(',').filter((item) => item !== ''));
        }
    });

    params.getAll('features').forEach((value) => {
        features.push(...value.split(',').filter((item) => item !== ''));
    });

    return [...new Set(features)];
}

function readLocation(params: URLSearchParams): string {
    const state = params.get('state') ?? '';
    const city = params.get('city') ?? '';

    if (state !== '') {
        return city !== '' ? `${state}||${city}` : state;
    }

    return Object.keys(props.locations).length === 1
        ? Object.keys(props.locations)[0]
        : '';
}

const initialParams = currentParams();

const location = ref(readLocation(initialParams));
const guests = ref<string | number>(initialParams.get('capacity') ?? '');
const event = ref(initialParams.get('event') ?? '');
const features = ref<string[]>(readFeatures(initialParams));

const activeFeatureCount = computed(() => features.value.length);

const locationGroups = computed(() =>
    Object.entries(props.locations).map(([state, data]) => ({
        state,
        label: data.label,
        cities: data.municipalities.map((city) => ({
            value: `${state}||${city}`,
            label: city,
        })),
    })),
);

function syncFromUrl(): void {
    const params = currentParams();

    location.value = readLocation(params);
    guests.value = params.get('capacity') ?? '';
    event.value = params.get('event') ?? '';
    features.value = readFeatures(params);
}

function clearFeatures(): void {
    features.value = [];
}

function submit(): void {
    const params: Record<string, string | number | string[]> = {};

    if (location.value !== '') {
        const [state, city] = location.value.split('||');

        if (state) {
            params.state = state;
        }

        if (city) {
            params.city = city;
        }
    }

    if (guests.value !== '' && guests.value !== null) {
        params.capacity = guests.value;
    }

    if (event.value !== '') {
        params.event = event.value;
    }

    if (features.value.length > 0) {
        params.features = features.value;
    }

    router.get(props.action, params);
}

watch(() => page.url, syncFromUrl);
</script>

<template>
    <form class="home-search p-3 sm:p-4" @submit.prevent="submit">
        <div
            class="grid gap-1 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto_auto] lg:items-stretch"
        >
            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:border-r lg:border-[color:var(--home-border)] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-location">
                    Ubicación
                </label>
                <select
                    id="home-location"
                    v-model="location"
                    class="home-select"
                >
                    <option value="">Todo México</option>
                    <optgroup
                        v-for="group in locationGroups"
                        :key="group.state"
                        :label="group.label"
                    >
                        <option :value="group.state">
                            Todo {{ group.label }}
                        </option>
                        <option
                            v-for="city in group.cities"
                            :key="city.value"
                            :value="city.value"
                        >
                            {{ city.label }}
                        </option>
                    </optgroup>
                </select>
            </div>

            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:border-r lg:border-[color:var(--home-border)] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-guests">
                    Invitados
                </label>
                <input
                    id="home-guests"
                    v-model="guests"
                    class="home-input"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    placeholder="¿Cuántos asisten?"
                />
            </div>

            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:border-r lg:border-[color:var(--home-border)] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-event">
                    Tipo de evento
                </label>
                <select id="home-event" v-model="event" class="home-select">
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

            <div class="p-1 lg:flex lg:items-stretch">
                <button
                    v-if="featuredCharacteristics.length > 0"
                    type="button"
                    class="home-btn home-btn--outline h-12 w-full gap-2 px-4 lg:w-auto"
                    :aria-expanded="filtersOpen"
                    aria-controls="home-features-panel"
                    @click="filtersOpen = !filtersOpen"
                >
                    <SlidersHorizontal
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <span>
                        Filtros
                        <span v-if="activeFeatureCount > 0">
                            ({{ activeFeatureCount }})
                        </span>
                    </span>
                    <ChevronDown
                        class="size-4 shrink-0 transition-transform duration-200 motion-reduce:transition-none"
                        :class="{ 'rotate-180': filtersOpen }"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <div class="p-1 lg:flex lg:items-stretch lg:pl-0">
                <button
                    type="submit"
                    class="home-btn home-btn--primary h-12 w-full px-6 lg:w-auto lg:px-7"
                >
                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <circle
                            cx="9"
                            cy="9"
                            r="6.25"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="M13.5 13.5L17.5 17.5"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                    Buscar
                </button>
            </div>
        </div>

        <div
            v-if="featuredCharacteristics.length > 0"
            id="home-features-panel"
            class="grid transition-[grid-template-rows,opacity] duration-300 ease-out motion-reduce:transition-none"
            :class="
                filtersOpen
                    ? 'grid-rows-[1fr] opacity-100'
                    : 'grid-rows-[0fr] opacity-0'
            "
            :inert="filtersOpen ? undefined : true"
            :aria-hidden="!filtersOpen"
        >
            <div class="min-h-0 overflow-hidden">
                <div
                    class="mt-3 border-t border-[color:var(--home-border)] pt-3"
                >
                    <div class="flex items-center justify-end">
                        <button
                            type="button"
                            class="text-sm font-semibold text-[color:var(--home-primary-deep)] transition-colors hover:text-[color:var(--home-primary-hover)] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="activeFeatureCount === 0"
                            @click="clearFeatures"
                        >
                            Limpiar
                        </button>
                    </div>

                    <FeaturedCharacteristicsPicker
                        v-model="features"
                        :options="featuredCharacteristics"
                        :show-descriptions="false"
                        :max="10"
                    />
                </div>
            </div>
        </div>
    </form>
</template>
