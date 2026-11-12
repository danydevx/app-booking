import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import type { ListingPublicCard } from '../types';

export type GuidedDateMode = 'exact' | 'approximate' | 'unknown';

export type GuidedLocation = {
    state: string | null;
    city: string | null;
    latitude: number | null;
    longitude: number | null;
    radiusKm: number | null;
};

export type GuidedSearchState = {
    dates: string[];
    dateMode: GuidedDateMode;
    approximateMonth: string | null;
    location: GuidedLocation;
    featureKeys: string[];
    eventSlug: string | null;
    guests: number | null;
    step: number;
};

export type GuidedResult = ListingPublicCard & {
    contact_url: string;
};

type GuidedResultsResponse = {
    listings: GuidedResult[];
    total: number;
    current_page: number;
    last_page: number;
};

const emptyLocation = (): GuidedLocation => ({
    state: null,
    city: null,
    latitude: null,
    longitude: null,
    radiusKm: null,
});

const defaultState = (): GuidedSearchState => ({
    dates: [],
    dateMode: 'exact',
    approximateMonth: null,
    location: emptyLocation(),
    featureKeys: [],
    eventSlug: null,
    guests: null,
    step: 1,
});

function clampStep(value: number): number {
    return Math.min(5, Math.max(1, value));
}

function readArray(params: URLSearchParams, key: string): string[] {
    const values: string[] = [];

    params.forEach((value, paramKey) => {
        if (paramKey === `${key}[]` || paramKey.startsWith(`${key}[`)) {
            values.push(value);
        }
    });

    params.getAll(key).forEach((value) => {
        values.push(...value.split(','));
    });

    return [...new Set(values.filter((value) => value !== ''))];
}

export function useGuidedSearch(resultsUrl: string) {
    const state = reactive<GuidedSearchState>(defaultState());
    const loading = ref(false);
    const loadingMore = ref(false);
    const searched = ref(false);
    const error = ref<string | null>(null);
    const results = ref<GuidedResult[]>([]);
    const total = ref(0);
    const currentPage = ref(1);
    const lastPage = ref(1);

    let controller: AbortController | null = null;

    function readUrl(): GuidedSearchState {
        const fallback = defaultState();

        if (typeof window === 'undefined') {
            return fallback;
        }

        const params = new URLSearchParams(window.location.search);
        const dateMode = params.get('date_mode');
        const step = Number.parseInt(params.get('step') ?? '1', 10);
        const lat = Number.parseFloat(params.get('lat') ?? '');
        const lng = Number.parseFloat(params.get('lng') ?? '');
        const radius = Number.parseFloat(params.get('radius') ?? '');
        const guests = Number.parseInt(params.get('guests') ?? '', 10);
        const month = params.get('month');

        return {
            dates: readArray(params, 'dates').filter((date) =>
                /^\d{4}-\d{2}-\d{2}$/.test(date),
            ),
            dateMode:
                dateMode === 'approximate' || dateMode === 'unknown'
                    ? dateMode
                    : 'exact',
            approximateMonth:
                month !== null && /^\d{4}-\d{2}$/.test(month) ? month : null,
            location: {
                state: params.get('state') || null,
                city: params.get('city') || null,
                latitude: Number.isFinite(lat) ? lat : null,
                longitude: Number.isFinite(lng) ? lng : null,
                radiusKm: Number.isFinite(radius) ? radius : null,
            },
            featureKeys: readArray(params, 'features'),
            eventSlug: params.get('event') || null,
            guests: Number.isFinite(guests) && guests > 0 ? guests : null,
            step: Number.isFinite(step) ? clampStep(step) : 1,
        };
    }

    function syncUrl(mode: 'push' | 'replace' = 'replace'): void {
        if (typeof window === 'undefined') {
            return;
        }

        const params = new URLSearchParams();

        params.set('step', String(state.step));

        if (state.dateMode !== 'exact') {
            params.set('date_mode', state.dateMode);
        }

        state.dates.forEach((date) => params.append('dates[]', date));

        if (state.dateMode === 'approximate' && state.approximateMonth) {
            params.set('month', state.approximateMonth);
        }

        if (
            state.location.latitude !== null &&
            state.location.longitude !== null
        ) {
            params.set('lat', String(state.location.latitude));
            params.set('lng', String(state.location.longitude));
            params.set('radius', String(state.location.radiusKm ?? 10));
        }

        if (state.location.state) {
            params.set('state', state.location.state);
        }

        if (state.location.city) {
            params.set('city', state.location.city);
        }

        state.featureKeys.forEach((key) => params.append('features[]', key));

        if (state.eventSlug) {
            params.set('event', state.eventSlug);
        }

        if (state.guests !== null) {
            params.set('guests', String(state.guests));
        }

        const url = new URL(window.location.href);
        url.search = params.toString();
        window.history[mode === 'push' ? 'pushState' : 'replaceState'](
            null,
            '',
            url,
        );
    }

    function hydrateFromUrl(): void {
        controller?.abort();
        loading.value = false;
        loadingMore.value = false;
        Object.assign(state, readUrl());

        if (state.step === 5) {
            void runSearch(false);
        }
    }

    function goToStep(step: number): void {
        state.step = clampStep(step);
        syncUrl('push');
    }

    function nextStep(): void {
        if (state.step < 5) {
            goToStep(state.step + 1);
        }
    }

    function previousStep(): void {
        if (state.step > 1) {
            goToStep(state.step - 1);
        }
    }

    function reset(): void {
        controller?.abort();
        Object.assign(state, defaultState());
        results.value = [];
        total.value = 0;
        currentPage.value = 1;
        lastPage.value = 1;
        searched.value = false;
        error.value = null;
        loading.value = false;
        loadingMore.value = false;
        syncUrl('replace');
    }

    function buildSearchParams(page: number): URLSearchParams {
        const params = new URLSearchParams();
        params.set('page', String(page));

        if (
            state.location.latitude !== null &&
            state.location.longitude !== null
        ) {
            params.set('lat', String(state.location.latitude));
            params.set('lng', String(state.location.longitude));
            params.set('radius', String(state.location.radiusKm ?? 10));
        } else {
            if (state.location.state) {
                params.set('state', state.location.state);
            }

            if (state.location.city) {
                params.set('city', state.location.city);
            }
        }

        state.featureKeys.forEach((key) => params.append('features[]', key));

        if (state.eventSlug) {
            params.set('event', state.eventSlug);
        }

        if (state.guests !== null) {
            params.set('capacity', String(state.guests));
        }

        return params;
    }

    async function fetchResults(page: number, append: boolean): Promise<void> {
        controller?.abort();
        controller = new AbortController();

        const isFirstPage = page === 1;

        if (isFirstPage) {
            loading.value = true;
            error.value = null;
            searched.value = true;

            if (!append) {
                results.value = [];
            }
        } else {
            loadingMore.value = true;
        }

        try {
            const response = await fetch(
                `${resultsUrl}?${buildSearchParams(page).toString()}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                throw new Error('No pudimos completar la búsqueda.');
            }

            const data = (await response.json()) as GuidedResultsResponse;

            results.value = append
                ? [...results.value, ...data.listings]
                : data.listings;
            total.value = data.total;
            currentPage.value = data.current_page;
            lastPage.value = data.last_page;
            error.value = null;
        } catch (exception) {
            if (
                exception instanceof DOMException &&
                exception.name === 'AbortError'
            ) {
                return;
            }

            error.value =
                'No pudimos completar la búsqueda. Revisa tu conexión e inténtalo de nuevo.';

            if (isFirstPage) {
                results.value = [];
                total.value = 0;
            }
        } finally {
            if (isFirstPage) {
                loading.value = false;
            } else {
                loadingMore.value = false;
            }
        }
    }

    async function runSearch(pushHistory = true): Promise<void> {
        if (loading.value) {
            return;
        }

        state.step = 5;

        if (pushHistory) {
            syncUrl('push');
        }

        await fetchResults(1, false);
    }

    async function loadMore(): Promise<void> {
        if (
            loading.value ||
            loadingMore.value ||
            currentPage.value >= lastPage.value
        ) {
            return;
        }

        await fetchResults(currentPage.value + 1, true);
    }

    watch(
        state,
        () => {
            syncUrl('replace');
        },
        { deep: true },
    );

    onMounted(() => {
        if (typeof window !== 'undefined') {
            hydrateFromUrl();
            window.addEventListener('popstate', hydrateFromUrl);
        }
    });

    onBeforeUnmount(() => {
        controller?.abort();

        if (typeof window !== 'undefined') {
            window.removeEventListener('popstate', hydrateFromUrl);
        }
    });

    return {
        state,
        loading,
        loadingMore,
        searched,
        error,
        results,
        total,
        currentPage,
        lastPage,
        goToStep,
        nextStep,
        previousStep,
        reset,
        runSearch,
        loadMore,
        retry: () => runSearch(false),
    };
}
