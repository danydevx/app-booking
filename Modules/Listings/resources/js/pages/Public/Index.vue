<script setup lang="ts">
import '@/homeSite';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import HomePagination from '@/components/home/HomePagination.vue';
import HomeVenueCard from '@/components/home/HomeVenueCard.vue';
import GeoSearchBar from '@/components/public/GeoSearchBar.vue';
import GeoResultsMap from '../../components/GeoResultsMap.vue';
import type {
    GeoBounds,
    GeoMapPoint,
    ListingLocations,
    ListingPublicCard,
    Paginated,
    PublicFilters,
    ServiceOption,
} from '../../types';

const props = defineProps<{
    listings: Paginated<ListingPublicCard>;
    mapListings: GeoMapPoint[];
    center: { lat: number; lng: number } | null;
    radius: number | null;
    bounds: GeoBounds | null;
    filters: PublicFilters;
    priorityServices: ServiceOption[];
    additionalServices: ServiceOption[];
    services: ServiceOption[];
    locations: ListingLocations;
    urls: {
        index: string;
        locations: string;
    };
}>();

const page = usePage();
const locationError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.location ??
        null,
);

const selectedId = ref<number | null>(null);
const mobileView = ref<'list' | 'map'>('list');
const pendingBounds = ref<GeoBounds | null>(null);
const mapRef = ref<InstanceType<typeof GeoResultsMap> | null>(null);

const hasZone = computed(() => props.bounds !== null);

const priceOptions: Record<string, string> = {
    '0-5000': 'Hasta $5,000',
    '5000-10000': '$5,000 – $10,000',
    '10000-20000': '$10,000 – $20,000',
    '20000-': 'Más de $20,000',
};

const locationLabel = computed(() => {
    if (props.filters.q) {
        return String(props.filters.q);
    }

    if (props.filters.postal_code) {
        return `CP ${props.filters.postal_code}`;
    }

    if (props.filters.city) {
        const state = props.filters.state
            ? props.locations[props.filters.state]?.label
            : null;

        return [props.filters.city, state].filter(Boolean).join(', ');
    }

    if (props.filters.state) {
        return (
            props.locations[props.filters.state]?.label ??
            String(props.filters.state)
        );
    }

    return null;
});

const priceLabel = computed(() => {
    const min = props.filters.price_min;
    const max = props.filters.price_max;

    if (min === null || min === undefined || min === '') {
        return null;
    }

    const key =
        max === null || max === undefined || max === ''
            ? `${min}-`
            : `${min}-${max}`;

    return priceOptions[key] ?? `$${min} – $${max ?? '∞'}`;
});

const serviceLabel = computed(() => {
    if (!props.filters.service) {
        return null;
    }

    return (
        props.services.find((option) => option.value === props.filters.service)
            ?.label ?? String(props.filters.service)
    );
});

const activeFilters = computed(() => {
    const entries: Array<{ label: string; value: string }> = [];

    if (locationLabel.value) {
        entries.push({ label: 'Ubicación', value: locationLabel.value });
    }

    if (props.filters.capacity) {
        entries.push({
            label: 'Invitados',
            value: `${props.filters.capacity}+`,
        });
    }

    if (priceLabel.value) {
        entries.push({ label: 'Precio', value: priceLabel.value });
    }

    if (serviceLabel.value) {
        entries.push({ label: 'Característica', value: serviceLabel.value });
    }

    if (hasZone.value) {
        entries.push({ label: 'Zona', value: 'Área visible del mapa' });
    }

    if (props.radius && props.center) {
        entries.push({ label: 'Radio', value: `${props.radius} km` });
    }

    return entries;
});

const showZoneButton = computed(() => {
    if (!pendingBounds.value) {
        return false;
    }

    if (!props.bounds) {
        return true;
    }

    return (['north', 'south', 'east', 'west'] as const).some(
        (key) =>
            Math.abs(pendingBounds.value![key] - props.bounds![key]) > 0.0005,
    );
});

function baseParams(): Record<string, string | number> {
    const params: Record<string, string | number> = {};
    const filters = props.filters;

    if (filters.q) {
        params.q = String(filters.q);
    }

    if (filters.state) {
        params.state = String(filters.state);
    }

    if (filters.city) {
        params.city = String(filters.city);
    }

    if (filters.postal_code) {
        params.postal_code = String(filters.postal_code);
    }

    if (filters.capacity) {
        params.capacity = Number(filters.capacity);
    }

    if (filters.service) {
        params.service = String(filters.service);
    }

    if (
        filters.price_min !== null &&
        filters.price_min !== undefined &&
        filters.price_min !== ''
    ) {
        params.price_min = Number(filters.price_min);
    }

    if (
        filters.price_max !== null &&
        filters.price_max !== undefined &&
        filters.price_max !== ''
    ) {
        params.price_max = Number(filters.price_max);
    }

    return params;
}

const clearZoneUrl = computed(() => {
    const params = new URLSearchParams(
        Object.entries(baseParams()).map(([key, value]) => [
            key,
            String(value),
        ]),
    );
    const query = params.toString();

    return query === '' ? props.urls.index : `${props.urls.index}?${query}`;
});

function onBoundsChange(bounds: GeoBounds): void {
    pendingBounds.value = bounds;
}

function applyZone(): void {
    const bounds = pendingBounds.value;

    if (!bounds) {
        return;
    }

    const round = (value: number): number => Math.round(value * 1e6) / 1e6;

    router.get(
        props.urls.index,
        {
            ...baseParams(),
            north: round(bounds.north),
            south: round(bounds.south),
            east: round(bounds.east),
            west: round(bounds.west),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                pendingBounds.value = null;
            },
        },
    );
}

function scrollToCard(id: number): void {
    nextTick(() => {
        document
            .getElementById(`venue-${id}`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}

function onCardSelect(id: number): void {
    selectedId.value = id;
}

function onMarkerSelect(id: number): void {
    selectedId.value = id;

    if (window.matchMedia('(max-width: 1023px)').matches) {
        mobileView.value = 'list';
    }

    scrollToCard(id);
}

function toggleView(view: 'list' | 'map'): void {
    mobileView.value = view;

    if (view === 'map') {
        nextTick(() => {
            window.setTimeout(() => mapRef.value?.refresh(), 80);
        });
    }
}

watch(
    () => props.mapListings.map((point) => point.id),
    (ids) => {
        if (selectedId.value !== null && !ids.includes(selectedId.value)) {
            selectedId.value = null;
        }
    },
);
</script>

<template>
    <Head title="Explorar terrazas" />

    <div class="flex h-[calc(100dvh-4.3125rem)] min-h-0 flex-col">
        <header
            class="shrink-0 border-b border-[color:var(--home-border)] bg-white"
        >
            <div class="w-full px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-baseline gap-3">
                        <h1 class="text-xl font-extrabold sm:text-2xl">
                            Explorar terrazas
                        </h1>
                        <span
                            class="text-sm font-medium text-[color:var(--home-muted)]"
                        >
                            {{ listings.total }}
                            {{ listings.total === 1 ? 'espacio' : 'espacios' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 lg:hidden">
                        <button
                            type="button"
                            class="home-tab"
                            :class="{
                                'home-tab--active': mobileView === 'list',
                            }"
                            :aria-pressed="mobileView === 'list'"
                            @click="toggleView('list')"
                        >
                            Lista
                        </button>
                        <button
                            type="button"
                            class="home-tab"
                            :class="{
                                'home-tab--active': mobileView === 'map',
                            }"
                            :aria-pressed="mobileView === 'map'"
                            @click="toggleView('map')"
                        >
                            Mapa
                        </button>
                    </div>
                </div>

                <div class="mt-3">
                    <GeoSearchBar
                        :action="urls.index"
                        :suggestions-url="urls.locations"
                        :filters="filters"
                        :services="services"
                        :locations="locations"
                    />
                    <p
                        v-if="locationError"
                        class="mt-2 text-sm font-medium text-[#b42318]"
                        role="alert"
                    >
                        {{ locationError }}
                    </p>
                </div>

                <div
                    v-if="activeFilters.length > 0"
                    class="mt-2 flex flex-wrap items-center gap-2"
                >
                    <span
                        v-for="filter in activeFilters"
                        :key="`${filter.label}-${filter.value}`"
                        class="home-chip"
                    >
                        {{ filter.label }}: {{ filter.value }}
                    </span>
                    <Link
                        v-if="hasZone"
                        :href="clearZoneUrl"
                        class="text-sm font-bold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                        preserve-scroll
                    >
                        Quitar zona
                    </Link>
                    <Link
                        :href="urls.index"
                        class="text-sm font-bold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                    >
                        Quitar filtros
                    </Link>
                </div>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <section
                class="relative min-w-0 lg:block lg:w-[58%] lg:flex-none"
                :class="
                    mobileView === 'map' ? 'block w-full' : 'hidden lg:block'
                "
            >
                <div class="absolute inset-0">
                    <GeoResultsMap
                        ref="mapRef"
                        :points="mapListings"
                        :center="center"
                        :radius="radius"
                        :bounds="bounds"
                        :selected-id="selectedId"
                        height="100%"
                        @select="onMarkerSelect"
                        @bounds-change="onBoundsChange"
                    />
                </div>

                <button
                    v-if="showZoneButton"
                    type="button"
                    class="home-btn home-btn--primary absolute top-3 left-1/2 z-[1000] -translate-x-1/2 shadow-lg"
                    @click="applyZone"
                >
                    Buscar en esta zona
                </button>

                <div
                    v-if="radius"
                    class="absolute bottom-3 left-3 z-[500] rounded-full border border-[color:var(--home-border)] bg-white/95 px-3 py-1 text-xs font-semibold text-[color:var(--home-secondary)]"
                >
                    Radio de {{ radius }} km
                </div>
            </section>

            <section
                class="min-w-0 flex-1 overflow-y-auto border-[color:var(--home-border)] bg-[color:var(--home-bg)] lg:w-[42%] lg:flex-none lg:border-l"
                :class="mobileView === 'list' ? 'block' : 'hidden lg:block'"
            >
                <div class="px-4 py-4 sm:px-5">
                    <div
                        v-if="listings.data.length === 0"
                        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white px-6 py-12 text-center"
                    >
                        <p class="text-xl font-extrabold sm:text-2xl">
                            {{
                                hasZone
                                    ? 'No encontramos terrazas en esta zona'
                                    : 'No encontramos espacios con esos criterios'
                            }}
                        </p>
                        <p
                            class="mx-auto mt-3 max-w-md text-base text-[color:var(--home-secondary)]"
                        >
                            Prueba con otra zona del mapa, un rango de invitados
                            distinto o menos características.
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <Link
                                v-if="hasZone"
                                :href="clearZoneUrl"
                                class="home-btn home-btn--outline"
                                preserve-scroll
                            >
                                Quitar zona
                            </Link>
                            <Link
                                :href="urls.index"
                                class="home-btn home-btn--primary"
                            >
                                Ver todo el catálogo
                            </Link>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <HomeVenueCard
                            v-for="listing in listings.data"
                            :key="listing.id"
                            :listing="listing"
                            selectable
                            :selected="selectedId === listing.id"
                            @select="onCardSelect"
                        />
                    </div>

                    <HomePagination :links="listings.links" />
                </div>
            </section>
        </div>
    </div>
</template>
