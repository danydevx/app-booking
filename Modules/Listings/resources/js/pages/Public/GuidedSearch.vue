<script setup lang="ts">
import '@/homeSite';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import type { PageSeo } from '@/types';
import ListingContactModal from '../../components/ListingContactModal.vue';
import EventDateStep from '../../components/guided/EventDateStep.vue';
import EventTypeStep from '../../components/guided/EventTypeStep.vue';
import GuidedSearchProgress from '../../components/guided/GuidedSearchProgress.vue';
import GuidedSearchResults from '../../components/guided/GuidedSearchResults.vue';
import LocationStep from '../../components/guided/LocationStep.vue';
import VenueFeaturesStep from '../../components/guided/VenueFeaturesStep.vue';
import {
    useGuidedSearch,
    type GuidedResult,
} from '../../composables/useGuidedSearch';

type EventTypeOption = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    icon: string | null;
};

type MunicipalityCenter = {
    state: string;
    city: string;
    latitude: number;
    longitude: number;
    listings_count: number;
};

const props = defineProps<{
    locations: Record<string, { label: string; municipalities: string[] }>;
    municipalityCenters: MunicipalityCenter[];
    featuredCharacteristics: Array<{
        key: string;
        name: string;
        description: string | null;
        icon: string | null;
    }>;
    eventTypes: EventTypeOption[];
    contact: {
        guest_ranges: Array<{ value: string; label: string }>;
        privacy_url: string | null;
        default_message: string;
        max_message_length: number;
        today: string;
        current_month: string;
    };
    seo: PageSeo;
    urls: {
        home: string;
        index: string;
        results: string;
    };
}>();

const guided = useGuidedSearch(props.urls.results);
const { state } = guided;
const contactListing = ref<GuidedResult | null>(null);
const contactOpen = ref(false);

const steps = [
    { label: 'Fecha' },
    { label: 'Ubicación' },
    { label: 'Características' },
    { label: 'Evento' },
    { label: 'Resultados' },
];

const selectedEvent = computed(() =>
    props.eventTypes.find((eventType) => eventType.slug === state.eventSlug),
);

const featureNames = computed(() =>
    state.featureKeys.map(
        (key) =>
            props.featuredCharacteristics.find(
                (characteristic) => characteristic.key === key,
            )?.name ?? key,
    ),
);

const dateSummary = computed(() => {
    if (state.dateMode === 'unknown') {
        return 'Fecha por definir';
    }

    if (state.dateMode === 'approximate') {
        return state.approximateMonth
            ? formatMonth(state.approximateMonth)
            : 'Mes por definir';
    }

    if (state.dates.length === 0) {
        return 'Sin fecha definida';
    }

    if (state.dates.length === 1) {
        return formatDate(state.dates[0]);
    }

    return `${state.dates.length} fechas alternativas`;
});

const locationSummary = computed(() => {
    if (state.location.city) {
        return [state.location.city, state.location.state]
            .filter(Boolean)
            .join(', ');
    }

    if (state.location.state) {
        return state.location.state;
    }

    if (state.location.latitude !== null && state.location.longitude !== null) {
        return `Punto en el mapa · ${state.location.radiusKm ?? 10} km`;
    }

    return 'Cualquier zona';
});

const summaryItems = computed(() => {
    const items: string[] = [];

    if (
        state.dates.length > 0 ||
        state.dateMode === 'approximate' ||
        state.dateMode === 'unknown'
    ) {
        items.push(dateSummary.value);
    }

    if (
        state.location.state ||
        state.location.city ||
        state.location.latitude !== null
    ) {
        items.push(locationSummary.value);
    }

    if (featureNames.value.length > 0) {
        items.push(featureNames.value.join(', '));
    }

    if (selectedEvent.value) {
        items.push(selectedEvent.value.name);
    }

    if (state.guests !== null) {
        items.push(`${state.guests} personas`);
    }

    return items;
});

const criteria = computed(() => {
    const items: Array<{ label: string; value: string; step: number }> = [];

    if (
        state.dates.length > 0 ||
        state.dateMode !== 'exact' ||
        state.approximateMonth
    ) {
        items.push({ label: 'Fecha', value: dateSummary.value, step: 1 });
    }

    if (
        state.location.state ||
        state.location.city ||
        state.location.latitude !== null
    ) {
        items.push({
            label: 'Ubicación',
            value: locationSummary.value,
            step: 2,
        });
    }

    if (featureNames.value.length > 0) {
        items.push({
            label: 'Características',
            value: featureNames.value.join(', '),
            step: 3,
        });
    }

    if (selectedEvent.value) {
        items.push({
            label: 'Evento',
            value: selectedEvent.value.name,
            step: 4,
        });
    }

    if (state.guests !== null) {
        items.push({
            label: 'Invitados',
            value: `${state.guests} personas`,
            step: 4,
        });
    }

    return items;
});

const dateNote = computed(() => {
    if (
        state.dates.length === 0 &&
        !state.approximateMonth &&
        state.dateMode !== 'unknown'
    ) {
        return null;
    }

    return 'Las fechas son una preferencia de búsqueda. La disponibilidad se confirma con el propietario.';
});

const primaryLabel = computed(() =>
    state.step === 4 ? 'Buscar espacios' : 'Continuar',
);

const contactPrefill = computed(() => {
    const dates = [...state.dates].sort((first, second) =>
        first.localeCompare(second),
    );
    const eventName = selectedEvent.value?.name ?? '';
    const message: string[] = [];

    message.push(
        eventName !== ''
            ? `Hola, me interesa un espacio para ${eventName}.`
            : 'Hola, me interesa encontrar un espacio para mi evento.',
    );

    if (state.guests !== null) {
        message.push(`Seríamos aproximadamente ${state.guests} personas.`);
    }

    if (dates.length > 0) {
        message.push(`Fechas de interés: ${dates.map(formatDate).join(', ')}.`);
    } else if (state.approximateMonth) {
        message.push(
            `Mes aproximado de interés: ${formatMonth(state.approximateMonth)}.`,
        );
    }

    if (featureNames.value.length > 0) {
        message.push(
            `Nos gustaría que el espacio tuviera: ${featureNames.value.join(', ')}.`,
        );
    }

    message.push('¿Podrías compartirme disponibilidad y detalles?');

    return {
        event_type: eventName !== '' ? eventName : null,
        guest_range: guestRangeFor(state.guests),
        event_date:
            state.dateMode === 'exact' && dates.length > 0 ? dates[0] : null,
        event_month:
            state.dateMode === 'approximate' ? state.approximateMonth : null,
        message: message.join(' '),
    };
});

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${date}T12:00:00`));
}

function formatMonth(month: string): string {
    const [year, value] = month.split('-').map(Number);

    if (!year || !value) {
        return month;
    }

    return new Intl.DateTimeFormat('es-MX', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, value - 1, 1));
}

function guestRangeFor(guests: number | null): string | null {
    if (guests === null) {
        return null;
    }

    if (guests <= 50) {
        return '1-50';
    }

    if (guests <= 100) {
        return '51-100';
    }

    if (guests <= 150) {
        return '101-150';
    }

    return '150+';
}

function handlePrimary(): void {
    if (state.step === 4) {
        void guided.runSearch();

        return;
    }

    guided.nextStep();
}

function openContact(listing: GuidedResult): void {
    contactListing.value = listing;
    contactOpen.value = true;
}
</script>

<template>
    <SeoHead :seo="seo" />

    <div class="home-container py-10 sm:py-14">
        <div class="mx-auto max-w-5xl">
            <div
                class="flex flex-wrap items-center justify-between gap-3 text-sm"
            >
                <Link
                    :href="urls.home"
                    class="font-semibold text-[color:var(--home-secondary)] hover:text-[color:var(--home-primary-deep)]"
                >
                    ← Volver al inicio
                </Link>
                <Link
                    :href="urls.index"
                    class="font-semibold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                >
                    Ver catálogo completo
                </Link>
            </div>

            <header class="mt-8 max-w-3xl">
                <p class="home-eyebrow">Búsqueda guiada</p>
                <h1
                    class="mt-2 text-3xl leading-tight font-extrabold sm:text-4xl lg:text-5xl"
                >
                    Encuentra tu espacio ideal
                </h1>
                <p
                    class="mt-3 text-base leading-relaxed text-[color:var(--home-secondary)] sm:text-lg"
                >
                    Responde unas preguntas y te mostraremos espacios que
                    encajen con tu celebración.
                </p>
            </header>

            <div class="mt-6">
                <GuidedSearchProgress :current="state.step" :steps="steps" />
            </div>

            <Transition name="guided-step" mode="out-in">
                <div :key="state.step" class="mt-8">
                    <EventDateStep
                        v-if="state.step === 1"
                        :dates="state.dates"
                        :date-mode="state.dateMode"
                        :approximate-month="state.approximateMonth"
                        :today="contact.today"
                        :current-month="contact.current_month"
                        @update:dates="state.dates = $event"
                        @update:date-mode="state.dateMode = $event"
                        @update:approximate-month="
                            state.approximateMonth = $event
                        "
                    />

                    <LocationStep
                        v-else-if="state.step === 2"
                        :locations="locations"
                        :municipality-centers="municipalityCenters"
                        :location="state.location"
                        @update:location="state.location = $event"
                    />

                    <VenueFeaturesStep
                        v-else-if="state.step === 3"
                        :model-value="state.featureKeys"
                        :options="featuredCharacteristics"
                        @update:model-value="state.featureKeys = $event"
                    />

                    <EventTypeStep
                        v-else-if="state.step === 4"
                        :event-types="eventTypes"
                        :event-slug="state.eventSlug"
                        :guests="state.guests"
                        :summary="summaryItems"
                        @update:event-slug="state.eventSlug = $event"
                        @update:guests="state.guests = $event"
                    />

                    <GuidedSearchResults
                        v-else
                        :listings="guided.results.value"
                        :total="guided.total.value"
                        :loading="guided.loading.value"
                        :loading-more="guided.loadingMore.value"
                        :searched="guided.searched.value"
                        :error="guided.error.value"
                        :current-page="guided.currentPage.value"
                        :last-page="guided.lastPage.value"
                        :criteria="criteria"
                        :date-note="dateNote"
                        @modify="guided.goToStep"
                        @restart="guided.reset"
                        @retry="guided.retry"
                        @load-more="guided.loadMore"
                        @contact="openContact"
                    />

                    <div
                        v-if="state.step < 5"
                        class="mt-8 flex flex-col-reverse gap-3 border-t border-[color:var(--home-border)] pt-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <button
                            v-if="state.step > 1"
                            type="button"
                            class="home-btn home-btn--outline w-full sm:w-auto"
                            @click="guided.previousStep()"
                        >
                            Regresar
                        </button>
                        <span v-else class="hidden sm:block" />

                        <button
                            type="button"
                            class="home-btn home-btn--primary w-full sm:w-auto"
                            @click="handlePrimary"
                        >
                            {{ primaryLabel }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </div>

    <ListingContactModal
        v-if="contactListing"
        v-model:open="contactOpen"
        :listing-name="contactListing.name"
        :store-url="contactListing.contact_url"
        :guest-ranges="contact.guest_ranges"
        :privacy-url="contact.privacy_url"
        :default-message="contact.default_message"
        :max-message-length="contact.max_message_length"
        :today="contact.today"
        :current-month="contact.current_month"
        :capacity="contactListing.capacity"
        :prefill="contactPrefill"
    />
</template>

<style scoped>
.guided-step-enter-active,
.guided-step-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.guided-step-enter-from {
    opacity: 0;
    transform: translateX(14px);
}

.guided-step-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

@media (prefers-reduced-motion: reduce) {
    .guided-step-enter-active,
    .guided-step-leave-active {
        transition: none;
    }

    .guided-step-enter-from,
    .guided-step-leave-to {
        transform: none;
    }
}
</style>
