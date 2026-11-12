<script setup lang="ts">
import '@/homeSite';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ListingMap from '../../components/ListingMap.vue';
import type { ListingMedia, ListingPublicDetail } from '../../types';

const props = defineProps<{
    listing: ListingPublicDetail;
    unavailableDates: Array<{
        date: string;
        status: string;
        label: string;
        reason: string | null;
    }>;
    urls: {
        index: string;
        report: string;
    };
}>();

const activeIndex = ref(0);
const images = computed(() =>
    props.listing.media.filter((media) => media.type === 'image'),
);
const videos = computed(() =>
    props.listing.media.filter((media) => media.type === 'video'),
);
const activeImage = computed<ListingMedia | null>(
    () => images.value[activeIndex.value] ?? images.value[0] ?? null,
);

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const lightboxImage = computed<ListingMedia | null>(
    () => images.value[lightboxIndex.value] ?? null,
);

const mapListing = computed(() => ({
    id: props.listing.id,
    name: props.listing.name,
    url: props.listing.url,
    latitude: props.listing.latitude,
    longitude: props.listing.longitude,
    distance_km: null,
}));

const availabilityUrl = computed(
    () => `/terrazas/${props.listing.slug}/disponibilidad`,
);

const location = computed(
    () =>
        [props.listing.city, props.listing.state].filter(Boolean).join(', ') ||
        props.listing.address ||
        'Ubicación por confirmar',
);

const contactEmail = computed(
    () => props.listing.contact_email ?? props.listing.account.contact_email,
);
const contactPhone = computed(
    () => props.listing.contact_phone ?? props.listing.account.contact_phone,
);
const whatsappUrl = computed(
    () => props.listing.whatsapp_url ?? props.listing.account.whatsapp_url,
);

function formatDate(value: string): string {
    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(year, month - 1, day));
}

function openLightbox(index: number): void {
    if (images.value.length === 0) {
        return;
    }

    lightboxIndex.value = index;
    activeIndex.value = index;
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
}

function closeLightbox(): void {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
}

function stepLightbox(direction: number): void {
    const total = images.value.length;

    if (total === 0) {
        return;
    }

    lightboxIndex.value = (lightboxIndex.value + direction + total) % total;
    activeIndex.value = lightboxIndex.value;
}

function onKeydown(event: KeyboardEvent): void {
    if (!lightboxOpen.value) {
        return;
    }

    if (event.key === 'Escape') {
        closeLightbox();
    } else if (event.key === 'ArrowRight') {
        stepLightbox(1);
    } else if (event.key === 'ArrowLeft') {
        stepLightbox(-1);
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head :title="`${listing.name} · Terrazas para eventos`" />

    <div class="home-container pt-6 pb-16 sm:pt-8">
        <Link
            :href="urls.index"
            class="text-sm font-semibold text-[color:var(--home-secondary)] hover:text-[color:var(--home-primary-deep)]"
        >
            ← Volver al catálogo
        </Link>

        <header class="mt-4 flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-3xl">
                <p class="home-eyebrow">{{ location }}</p>
                <h1
                    class="mt-2 text-3xl leading-tight font-extrabold sm:text-4xl lg:text-5xl"
                >
                    {{ listing.name }}
                </h1>
                <p
                    v-if="listing.address"
                    class="mt-2 text-base text-[color:var(--home-secondary)]"
                >
                    {{ listing.address }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span v-if="listing.capacity" class="home-chip">
                        Hasta {{ listing.capacity }} invitados
                    </span>
                    <span
                        v-if="listing.pricing.price_from_formatted"
                        class="home-chip"
                    >
                        Desde {{ listing.pricing.price_from_formatted }}
                    </span>
                    <span
                        v-for="detail in listing.details.filter(
                            (item) => item.key === 'space_type',
                        )"
                        :key="detail.key"
                        class="home-chip"
                    >
                        {{ detail.value }}
                    </span>
                </div>
            </div>

            <div class="hidden flex-wrap gap-2 sm:flex">
                <Link
                    :href="availabilityUrl"
                    class="home-btn home-btn--primary"
                >
                    Consultar disponibilidad
                </Link>
                <a
                    v-if="whatsappUrl"
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="home-btn home-btn--dark"
                >
                    WhatsApp
                </a>
            </div>
        </header>

        <div class="mt-6 grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <button
                type="button"
                class="home-media aspect-[16/10] overflow-hidden rounded-[1.125rem]"
                :aria-label="
                    activeImage
                        ? `Ampliar fotografía de ${listing.name}`
                        : 'Sin fotografías'
                "
                @click="openLightbox(activeIndex)"
            >
                <img
                    v-if="activeImage"
                    :src="activeImage.url"
                    :alt="activeImage.alt_text ?? listing.name"
                    fetchpriority="high"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center bg-[color:var(--home-soft)]"
                >
                    <span
                        class="text-xl font-bold text-[color:var(--home-primary)]/60"
                    >
                        Sin fotografías todavía
                    </span>
                </div>
            </button>

            <div
                v-if="images.length > 1"
                class="grid grid-cols-3 gap-3 lg:max-h-[27rem] lg:grid-cols-2 lg:overflow-y-auto"
            >
                <button
                    v-for="(image, index) in images"
                    :key="image.id"
                    type="button"
                    class="home-media aspect-square overflow-hidden rounded-[0.75rem]"
                    :class="
                        index === activeIndex
                            ? 'ring-2 ring-[color:var(--home-primary)] ring-offset-2 ring-offset-[color:var(--home-bg)]'
                            : 'opacity-85 hover:opacity-100'
                    "
                    :aria-label="`Ver fotografía ${index + 1}`"
                    @click="openLightbox(index)"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt_text ?? ''"
                        loading="lazy"
                        decoding="async"
                    />
                </button>
            </div>
        </div>
        <p
            v-if="images.length > 0"
            class="mt-2 text-xs text-[color:var(--home-muted)]"
        >
            Toca una fotografía para verla en grande.
        </p>

        <div v-if="videos.length > 0" class="mt-8">
            <p class="home-eyebrow">Video</p>
            <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                Conoce el espacio
            </h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <video
                    v-for="video in videos"
                    :key="video.id"
                    :src="video.url"
                    controls
                    class="aspect-video w-full rounded-[1.125rem] border border-[color:var(--home-border)] bg-black"
                />
            </div>
        </div>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-10">
                <section v-if="listing.description">
                    <p class="home-eyebrow">El espacio</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Sobre el lugar
                    </h2>
                    <p
                        class="mt-4 text-base leading-relaxed whitespace-pre-line text-[color:var(--home-secondary)]"
                    >
                        {{ listing.description }}
                    </p>
                </section>

                <section
                    v-if="listing.serviceGroups.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Características</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Lo que ofrece el lugar
                    </h2>
                    <div class="mt-5 space-y-5">
                        <div
                            v-for="group in listing.serviceGroups"
                            :key="group.key"
                        >
                            <h3
                                class="text-xs font-bold tracking-[0.12em] text-[color:var(--home-muted)] uppercase"
                            >
                                {{ group.label }}
                            </h3>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="service in group.services"
                                    :key="service.value"
                                    class="home-chip"
                                >
                                    {{ service.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="
                        listing.pricing.has_pricing ||
                        listing.priceRanges.length > 0
                    "
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Precios</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Tarifas del espacio
                    </h2>
                    <p class="mt-2 text-sm text-[color:var(--home-muted)]">
                        Información orientativa; confirma los detalles con el
                        anfitrión.
                    </p>

                    <div
                        v-if="listing.pricing.price_from_formatted"
                        class="mt-5 inline-flex items-baseline gap-2 rounded-[0.875rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] px-4 py-3"
                    >
                        <span class="home-eyebrow !mb-0 !text-[0.6875rem]">
                            Desde
                        </span>
                        <span
                            class="text-2xl font-extrabold text-[color:var(--home-primary-deep)]"
                        >
                            {{ listing.pricing.price_from_formatted }}
                        </span>
                    </div>

                    <div
                        v-if="
                            listing.pricing.daily_prices.some(
                                (day) => day.has_data,
                            )
                        "
                        class="mt-5 overflow-x-auto rounded-[0.875rem] border border-[color:var(--home-border)]"
                    >
                        <table
                            class="w-full min-w-[34rem] border-collapse text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-[color:var(--home-border)] text-left"
                                >
                                    <th
                                        class="px-3 py-2 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                                    >
                                        Día
                                    </th>
                                    <th
                                        class="px-3 py-2 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                                    >
                                        Horas
                                    </th>
                                    <th
                                        class="px-3 py-2 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                                    >
                                        Entrada
                                    </th>
                                    <th
                                        class="px-3 py-2 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                                    >
                                        Salida
                                    </th>
                                    <th
                                        class="px-3 py-2 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                                    >
                                        Precio
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="day in listing.pricing.daily_prices"
                                    :key="day.key"
                                >
                                    <tr
                                        v-if="day.has_data"
                                        class="border-b border-[color:var(--home-border)] last:border-0"
                                    >
                                        <td class="px-3 py-2 font-semibold">
                                            {{ day.label }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ day.hours ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ day.entry_time_label ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ day.exit_time_label ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 font-semibold">
                                            {{
                                                day.formatted ??
                                                'No especificado'
                                            }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <p
                        v-if="
                            listing.pricing.included_hours ||
                            listing.pricing.included_people
                        "
                        class="mt-4 text-sm text-[color:var(--home-secondary)]"
                    >
                        Incluye
                        <span v-if="listing.pricing.included_hours">
                            {{ listing.pricing.included_hours }} horas
                        </span>
                        <span
                            v-if="
                                listing.pricing.included_hours &&
                                listing.pricing.included_people
                            "
                        >
                            para
                        </span>
                        <span v-if="listing.pricing.included_people">
                            {{ listing.pricing.included_people }} personas
                        </span>
                        .
                    </p>

                    <div
                        v-if="
                            listing.pricing.extra_hour_formatted ||
                            listing.pricing.extra_person_formatted ||
                            listing.pricing.deposit_required_formatted
                        "
                        class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-[color:var(--home-secondary)]"
                    >
                        <span v-if="listing.pricing.extra_hour_formatted">
                            Hora adicional:
                            {{ listing.pricing.extra_hour_formatted }}
                        </span>
                        <span v-if="listing.pricing.extra_person_formatted">
                            Persona adicional:
                            {{ listing.pricing.extra_person_formatted }}
                        </span>
                        <span v-if="listing.pricing.deposit_required_formatted">
                            Anticipo requerido:
                            {{ listing.pricing.deposit_required_formatted }}
                        </span>
                    </div>

                    <p
                        v-if="listing.pricing.price_notes"
                        class="mt-3 text-sm whitespace-pre-line text-[color:var(--home-secondary)]"
                    >
                        {{ listing.pricing.price_notes }}
                    </p>

                    <div
                        v-if="listing.priceRanges.length > 0"
                        class="mt-8 space-y-4"
                    >
                        <h3 class="text-lg font-bold">Tarifas por temporada</h3>
                        <div class="grid gap-4">
                            <article
                                v-for="range in listing.priceRanges"
                                :key="range.id"
                                class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-4"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <h4 class="font-bold">{{ range.name }}</h4>
                                    <span
                                        class="home-chip"
                                        :class="{
                                            'border-[color:var(--home-primary)]/40 bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]':
                                                range.status.value ===
                                                'current',
                                        }"
                                    >
                                        {{ range.status.label }}
                                    </span>
                                </div>
                                <p
                                    class="mt-1 text-xs text-[color:var(--home-muted)]"
                                >
                                    {{ range.starts_at_label }} al
                                    {{ range.ends_at_label }}
                                </p>
                                <p
                                    v-if="range.price_formatted"
                                    class="mt-2 text-sm font-bold text-[color:var(--home-primary-deep)]"
                                >
                                    Desde {{ range.price_formatted }}
                                </p>

                                <div
                                    v-if="
                                        range.daily_pricing.some(
                                            (day) => day.has_data,
                                        )
                                    "
                                    class="mt-3 overflow-x-auto rounded-lg border border-[color:var(--home-border)]"
                                >
                                    <table
                                        class="w-full min-w-[30rem] border-collapse text-sm"
                                    >
                                        <thead>
                                            <tr
                                                class="border-b border-[color:var(--home-border)] text-left"
                                            >
                                                <th
                                                    class="px-3 py-2 text-xs font-bold text-[color:var(--home-muted)] uppercase"
                                                >
                                                    Día
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-xs font-bold text-[color:var(--home-muted)] uppercase"
                                                >
                                                    Horas
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-xs font-bold text-[color:var(--home-muted)] uppercase"
                                                >
                                                    Entrada
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-xs font-bold text-[color:var(--home-muted)] uppercase"
                                                >
                                                    Salida
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-xs font-bold text-[color:var(--home-muted)] uppercase"
                                                >
                                                    Precio
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-for="day in range.daily_pricing"
                                                :key="day.key"
                                            >
                                                <tr
                                                    v-if="day.has_data"
                                                    class="border-b border-[color:var(--home-border)] last:border-0"
                                                >
                                                    <td
                                                        class="px-3 py-2 font-semibold"
                                                    >
                                                        {{ day.label }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ day.hours ?? '—' }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{
                                                            day.entry_time_label ??
                                                            '—'
                                                        }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{
                                                            day.exit_time_label ??
                                                            '—'
                                                        }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-2 font-semibold"
                                                    >
                                                        {{
                                                            day.formatted ??
                                                            'No especificado'
                                                        }}
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <p
                                    v-if="range.included_people"
                                    class="mt-2 text-sm text-[color:var(--home-secondary)]"
                                >
                                    Personas incluidas:
                                    {{ range.included_people }}
                                </p>
                                <p
                                    v-if="
                                        range.extra_hour_formatted ||
                                        range.extra_person_formatted ||
                                        range.deposit_required_formatted
                                    "
                                    class="mt-1 text-xs text-[color:var(--home-muted)]"
                                >
                                    <span v-if="range.extra_hour_formatted">
                                        Hora adicional:
                                        {{ range.extra_hour_formatted }}
                                    </span>
                                    <span v-if="range.extra_person_formatted">
                                        · Persona adicional:
                                        {{ range.extra_person_formatted }}
                                    </span>
                                    <span
                                        v-if="range.deposit_required_formatted"
                                    >
                                        · Anticipo:
                                        {{ range.deposit_required_formatted }}
                                    </span>
                                </p>
                                <p
                                    v-if="range.conditions"
                                    class="mt-2 text-xs text-[color:var(--home-muted)]"
                                >
                                    {{ range.conditions }}
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <section
                    v-if="listing.promotions.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Promociones</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Vigentes por tiempo limitado
                    </h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <article
                            v-for="promotion in listing.promotions"
                            :key="promotion.id"
                            class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                        >
                            <h3 class="text-lg font-bold">
                                {{ promotion.title }}
                            </h3>
                            <div
                                class="mt-2 flex flex-wrap items-baseline gap-2"
                            >
                                <span
                                    v-if="promotion.promotional_price_formatted"
                                    class="text-xl font-extrabold text-[color:var(--home-primary-deep)]"
                                >
                                    {{ promotion.promotional_price_formatted }}
                                </span>
                                <span
                                    v-if="promotion.previous_price_formatted"
                                    class="text-sm text-[color:var(--home-muted)] line-through"
                                >
                                    {{ promotion.previous_price_formatted }}
                                </span>
                            </div>
                            <p
                                class="mt-2 text-xs text-[color:var(--home-muted)]"
                            >
                                {{ promotion.starts_at_label }} al
                                {{ promotion.ends_at_label }}
                            </p>
                            <p
                                v-if="promotion.applies_on.length > 0"
                                class="mt-1 text-xs text-[color:var(--home-muted)]"
                            >
                                Días: {{ promotion.applies_on.join(', ') }}
                            </p>
                            <p
                                v-if="promotion.description"
                                class="mt-3 text-sm whitespace-pre-line text-[color:var(--home-secondary)]"
                            >
                                {{ promotion.description }}
                            </p>
                            <p
                                v-if="promotion.conditions"
                                class="mt-2 text-xs text-[color:var(--home-muted)]"
                            >
                                {{ promotion.conditions }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    v-if="listing.commercialServices.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Servicios</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Servicios disponibles
                    </h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <article
                            v-for="service in listing.commercialServices"
                            :key="service.id"
                            class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold">{{ service.label }}</h3>
                                <span class="home-chip">
                                    {{ service.pricing_mode_label }}
                                </span>
                                <span
                                    v-if="service.provider === 'external'"
                                    class="home-chip"
                                >
                                    {{ service.provider_label }}
                                </span>
                            </div>
                            <p
                                v-if="
                                    service.pricing_mode === 'additional' &&
                                    service.has_price
                                "
                                class="mt-2 text-sm font-bold text-[color:var(--home-primary-deep)]"
                            >
                                {{ service.price_formatted }} ·
                                {{ service.price_unit_label }}
                            </p>
                            <p
                                v-else-if="service.pricing_mode === 'quote'"
                                class="mt-2 text-sm text-[color:var(--home-secondary)]"
                            >
                                Precio a consultar con el anfitrión.
                            </p>
                            <p
                                v-else
                                class="mt-2 text-sm text-[color:var(--home-secondary)]"
                            >
                                Incluido en la renta.
                            </p>
                            <p
                                v-if="service.description"
                                class="mt-2 text-sm whitespace-pre-line text-[color:var(--home-secondary)]"
                            >
                                {{ service.description }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    v-if="listing.packages.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Paquetes</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Opciones de renta
                    </h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="packageItem in listing.packages"
                            :key="packageItem.id"
                            class="flex flex-col rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                        >
                            <h3 class="text-lg font-bold">
                                {{ packageItem.name }}
                            </h3>
                            <p
                                class="mt-1 text-xl font-extrabold text-[color:var(--home-primary-deep)]"
                            >
                                {{ packageItem.price_formatted }}
                            </p>
                            <p
                                class="mt-1 text-sm text-[color:var(--home-secondary)]"
                            >
                                {{ packageItem.duration_hours }} horas ·
                                {{ packageItem.included_people }} personas
                            </p>
                            <p
                                v-if="packageItem.description"
                                class="mt-2 text-sm whitespace-pre-line text-[color:var(--home-secondary)]"
                            >
                                {{ packageItem.description }}
                            </p>
                            <div
                                v-if="packageItem.services.length > 0"
                                class="mt-3"
                            >
                                <p
                                    class="text-xs font-bold tracking-[0.1em] text-[color:var(--home-muted)] uppercase"
                                >
                                    Servicios incluidos
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="service in packageItem.services"
                                        :key="service.key"
                                        class="home-chip"
                                    >
                                        {{ service.label }}
                                    </span>
                                </div>
                            </div>
                            <p
                                v-if="packageItem.conditions"
                                class="mt-3 text-xs text-[color:var(--home-muted)]"
                            >
                                {{ packageItem.conditions }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    v-if="listing.details.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Detalles</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Del espacio
                    </h2>
                    <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        <div
                            v-for="detail in listing.details"
                            :key="detail.key"
                            class="border-b border-[color:var(--home-border)] pb-3"
                        >
                            <dt
                                class="text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                            >
                                {{ detail.label }}
                            </dt>
                            <dd class="mt-1 text-base font-medium">
                                {{ detail.value }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="listing.rules"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Reglas</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Antes de reservar
                    </h2>
                    <p
                        class="mt-4 text-base leading-relaxed whitespace-pre-line text-[color:var(--home-secondary)]"
                    >
                        {{ listing.rules }}
                    </p>
                </section>

                <section
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Ubicación</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Dónde está
                    </h2>
                    <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                        {{ listing.address }}
                        <span v-if="listing.city">
                            · {{ listing.city }}, {{ listing.state }}
                        </span>
                    </p>
                    <div class="mt-4 overflow-hidden rounded-[1.125rem]">
                        <ListingMap
                            :listings="[mapListing]"
                            :center="
                                listing.latitude !== null &&
                                listing.longitude !== null
                                    ? {
                                          lat: listing.latitude,
                                          lng: listing.longitude,
                                      }
                                    : null
                            "
                            :zoom="14"
                            height="22rem"
                        />
                    </div>
                </section>
            </div>

            <aside
                class="order-first min-w-0 space-y-4 lg:sticky lg:top-24 lg:order-none lg:h-fit"
            >
                <div
                    class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                >
                    <div class="flex items-baseline justify-between gap-3">
                        <div>
                            <p class="text-sm text-[color:var(--home-muted)]">
                                {{
                                    listing.pricing.price_from_formatted
                                        ? 'Desde'
                                        : 'Precio'
                                }}
                            </p>
                            <p class="text-2xl font-extrabold">
                                {{
                                    listing.pricing.price_from_formatted ??
                                    'A consultar'
                                }}
                            </p>
                        </div>
                        <span v-if="listing.capacity" class="home-chip">
                            Hasta {{ listing.capacity }}
                        </span>
                    </div>

                    <div
                        v-if="listing.account.logo_url || listing.account.name"
                        class="mt-4 flex items-center gap-3 border-t border-[color:var(--home-border)] pt-4"
                    >
                        <img
                            v-if="listing.account.logo_url"
                            :src="listing.account.logo_url"
                            :alt="listing.account.name ?? listing.name"
                            class="size-10 rounded-[0.625rem] border border-[color:var(--home-border)] object-cover"
                        />
                        <div>
                            <p
                                class="text-xs font-bold tracking-[0.1em] text-[color:var(--home-muted)] uppercase"
                            >
                                Anfitrión
                            </p>
                            <p class="text-sm font-semibold">
                                {{ listing.account.name }}
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-[color:var(--home-secondary)]">
                        La reserva y el anticipo se acuerdan directamente con el
                        anfitrión.
                    </p>

                    <div class="mt-4 flex flex-col gap-2">
                        <Link
                            :href="availabilityUrl"
                            class="home-btn home-btn--primary w-full"
                        >
                            Consultar disponibilidad
                        </Link>
                        <a
                            v-if="whatsappUrl"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="home-btn home-btn--dark w-full"
                        >
                            Abrir WhatsApp
                        </a>
                        <a
                            v-if="contactEmail"
                            :href="`mailto:${contactEmail}`"
                            class="home-btn home-btn--outline w-full"
                        >
                            Enviar correo
                        </a>
                        <a
                            v-if="contactPhone"
                            :href="`tel:${contactPhone}`"
                            class="home-btn home-btn--outline w-full"
                        >
                            Llamar
                        </a>
                    </div>

                    <p
                        v-if="listing.contact_name"
                        class="mt-4 text-xs text-[color:var(--home-muted)]"
                    >
                        Contacto: {{ listing.contact_name }}
                    </p>
                    <p class="mt-1 text-xs text-[color:var(--home-muted)]">
                        WhatsApp abre una conversación manual; la plataforma no
                        envía el mensaje.
                    </p>
                </div>

                <div
                    v-if="unavailableDates.length > 0"
                    class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                >
                    <h2 class="text-lg font-bold">
                        Próximas fechas no disponibles
                    </h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="date in unavailableDates"
                            :key="date.date"
                            class="flex items-center justify-between gap-3 border-b border-[color:var(--home-border)] pb-2 last:border-0 last:pb-0"
                        >
                            <span class="text-[color:var(--home-secondary)]">
                                {{ formatDate(date.date) }}
                            </span>
                            <span class="text-xs font-semibold">
                                {{ date.label }}
                            </span>
                        </li>
                    </ul>
                    <Link
                        :href="availabilityUrl"
                        class="home-btn home-btn--outline mt-4 w-full"
                    >
                        Ver calendario completo
                    </Link>
                </div>

                <p
                    class="text-xs leading-relaxed text-[color:var(--home-muted)]"
                >
                    ¿Este anuncio parece fraudulento o incorrecto?
                    <Link
                        :href="urls.report"
                        class="font-semibold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                    >
                        Reportar terraza
                    </Link>
                </p>
            </aside>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="lightboxOpen && lightboxImage"
            class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-black/92 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Galería de fotografías"
            @click.self="closeLightbox"
        >
            <div
                class="absolute inset-x-4 top-4 flex items-center justify-between"
            >
                <p class="text-sm font-semibold text-white/80">
                    {{ lightboxIndex + 1 }} / {{ images.length }}
                </p>
                <button
                    type="button"
                    class="flex size-10 items-center justify-center rounded-full bg-white/15 text-xl text-white hover:bg-white/25"
                    aria-label="Cerrar galería"
                    @click="closeLightbox"
                >
                    ×
                </button>
            </div>

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute top-1/2 left-3 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25"
                aria-label="Fotografía anterior"
                @click="stepLightbox(-1)"
            >
                ‹
            </button>

            <img
                :src="lightboxImage.url"
                :alt="lightboxImage.alt_text ?? listing.name"
                class="max-h-[82vh] max-w-full rounded-[0.75rem] object-contain"
            />

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute top-1/2 right-3 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25"
                aria-label="Fotografía siguiente"
                @click="stepLightbox(1)"
            >
                ›
            </button>
        </div>
    </Teleport>
</template>
