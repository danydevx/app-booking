<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicShell from '@/components/PublicShell.vue';
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
const mapListing = computed(() => ({
    id: props.listing.id,
    name: props.listing.name,
    url: props.listing.url,
    latitude: props.listing.latitude,
    longitude: props.listing.longitude,
    distance_km: null,
}));

const contactEmail = computed(
    () => props.listing.contact_email ?? props.listing.account.contact_email,
);
const contactPhone = computed(
    () => props.listing.contact_phone ?? props.listing.account.contact_phone,
);
const whatsappUrl = computed(
    () => props.listing.whatsapp_url ?? props.listing.account.whatsapp_url,
);
</script>

<template>
    <Head :title="listing.name" />

    <PublicShell>
        <div class="space-y-8">
            <div class="space-y-2">
                <Link
                    :href="urls.index"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Volver a explorar
                </Link>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight">
                            {{ listing.name }}
                        </h1>
                        <p class="text-muted-foreground">
                            {{
                                [listing.city, listing.state]
                                    .filter(Boolean)
                                    .join(', ') || 'Ubicación por confirmar'
                            }}
                            <span v-if="listing.capacity">
                                · Hasta {{ listing.capacity }} personas
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-xl border bg-muted">
                        <img
                            v-if="activeImage"
                            :src="activeImage.url"
                            :alt="activeImage.alt_text ?? listing.name"
                            class="aspect-video w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex aspect-video items-center justify-center text-sm text-muted-foreground"
                        >
                            Sin imágenes
                        </div>
                    </div>

                    <div
                        v-if="images.length > 1"
                        class="grid grid-cols-4 gap-2 sm:grid-cols-6"
                    >
                        <button
                            v-for="(image, index) in images"
                            :key="image.id"
                            type="button"
                            class="overflow-hidden rounded-lg border"
                            :class="
                                index === activeIndex
                                    ? 'ring-2 ring-primary'
                                    : 'opacity-80 hover:opacity-100'
                            "
                            @click="activeIndex = index"
                        >
                            <img
                                :src="image.url"
                                :alt="image.alt_text ?? ''"
                                class="aspect-square w-full object-cover"
                            />
                        </button>
                    </div>

                    <div v-if="videos.length > 0" class="space-y-3">
                        <h2 class="text-lg font-semibold">Videos</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <video
                                v-for="video in videos"
                                :key="video.id"
                                :src="video.url"
                                controls
                                class="aspect-video w-full rounded-xl border bg-black"
                            />
                        </div>
                    </div>

                    <section v-if="listing.description" class="space-y-2">
                        <h2 class="text-lg font-semibold">Sobre el espacio</h2>
                        <p class="whitespace-pre-line text-muted-foreground">
                            {{ listing.description }}
                        </p>
                    </section>

                    <section
                        v-if="
                            listing.pricing.has_pricing ||
                            listing.priceRanges.length > 0
                        "
                        class="space-y-4 rounded-xl border bg-card p-5"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Precios</h2>
                            <p class="text-sm text-muted-foreground">
                                Información orientativa; confirma los detalles
                                con el anfitrión.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div
                                v-if="listing.pricing.price_from_formatted"
                                class="rounded-lg border p-3"
                            >
                                <p class="text-xs text-muted-foreground">
                                    Desde
                                </p>
                                <p class="text-xl font-semibold">
                                    {{ listing.pricing.price_from_formatted }}
                                </p>
                            </div>
                            <template
                                v-for="day in listing.pricing.daily_prices"
                                :key="day.key"
                            >
                                <div
                                    v-if="day.formatted"
                                    class="rounded-lg border p-3"
                                >
                                    <p class="text-xs text-muted-foreground">
                                        {{ day.label }}
                                    </p>
                                    <p class="text-xl font-semibold">
                                        {{ day.formatted }}
                                    </p>
                                </div>
                            </template>
                        </div>
                        <p
                            v-if="
                                listing.pricing.included_hours ||
                                listing.pricing.included_people
                            "
                            class="text-sm text-muted-foreground"
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
                            class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground"
                        >
                            <span v-if="listing.pricing.extra_hour_formatted">
                                Hora adicional:
                                {{ listing.pricing.extra_hour_formatted }}
                            </span>
                            <span v-if="listing.pricing.extra_person_formatted">
                                Persona adicional:
                                {{ listing.pricing.extra_person_formatted }}
                            </span>
                            <span
                                v-if="
                                    listing.pricing.deposit_required_formatted
                                "
                            >
                                Anticipo requerido:
                                {{ listing.pricing.deposit_required_formatted }}
                            </span>
                        </div>
                        <p
                            v-if="listing.pricing.price_notes"
                            class="text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ listing.pricing.price_notes }}
                        </p>

                        <div
                            v-if="listing.priceRanges.length > 0"
                            class="space-y-3 border-t pt-4"
                        >
                            <h3 class="text-sm font-semibold">
                                Tarifas por temporada
                            </h3>
                            <div class="grid gap-3 md:grid-cols-2">
                                <article
                                    v-for="range in listing.priceRanges"
                                    :key="range.id"
                                    class="rounded-lg border p-3"
                                >
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2"
                                    >
                                        <h4 class="font-medium">
                                            {{ range.name }}
                                        </h4>
                                        <Badge
                                            :variant="
                                                range.status.value === 'current'
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{ range.status.label }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-lg font-semibold">
                                        {{ range.price_formatted }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ range.starts_at_label }} al
                                        {{ range.ends_at_label }}
                                    </p>
                                    <p
                                        v-if="
                                            range.included_hours ||
                                            range.included_people
                                        "
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Incluye
                                        <span v-if="range.included_hours">
                                            {{ range.included_hours }} horas
                                        </span>
                                        <span
                                            v-if="
                                                range.included_hours &&
                                                range.included_people
                                            "
                                        >
                                            para
                                        </span>
                                        <span v-if="range.included_people">
                                            {{ range.included_people }} personas
                                        </span>
                                        .
                                    </p>
                                    <p
                                        v-if="
                                            range.extra_hour_formatted ||
                                            range.extra_person_formatted ||
                                            range.deposit_required_formatted
                                        "
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        <span v-if="range.extra_hour_formatted">
                                            Hora adicional:
                                            {{ range.extra_hour_formatted }}
                                        </span>
                                        <span
                                            v-if="range.extra_person_formatted"
                                        >
                                            · Persona adicional:
                                            {{ range.extra_person_formatted }}
                                        </span>
                                        <span
                                            v-if="
                                                range.deposit_required_formatted
                                            "
                                        >
                                            · Anticipo:
                                            {{
                                                range.deposit_required_formatted
                                            }}
                                        </span>
                                    </p>
                                    <p
                                        v-if="range.conditions"
                                        class="mt-2 text-xs text-muted-foreground"
                                    >
                                        {{ range.conditions }}
                                    </p>
                                </article>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="listing.promotions.length > 0"
                        class="space-y-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Promociones</h2>
                            <p class="text-sm text-muted-foreground">
                                Promociones vigentes por tiempo limitado.
                            </p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <article
                                v-for="promotion in listing.promotions"
                                :key="promotion.id"
                                class="rounded-xl border border-amber-300 bg-amber-50/60 p-4 dark:border-amber-900 dark:bg-amber-950/20"
                            >
                                <h3 class="font-semibold">
                                    {{ promotion.title }}
                                </h3>
                                <div
                                    class="mt-2 flex flex-wrap items-baseline gap-2"
                                >
                                    <span
                                        v-if="
                                            promotion.promotional_price_formatted
                                        "
                                        class="text-xl font-semibold"
                                    >
                                        {{
                                            promotion.promotional_price_formatted
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            promotion.previous_price_formatted
                                        "
                                        class="text-sm text-muted-foreground line-through"
                                    >
                                        {{ promotion.previous_price_formatted }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ promotion.starts_at_label }} al
                                    {{ promotion.ends_at_label }}
                                </p>
                                <p
                                    v-if="promotion.applies_on.length > 0"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    Días: {{ promotion.applies_on.join(', ') }}
                                </p>
                                <p
                                    v-if="promotion.description"
                                    class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                                >
                                    {{ promotion.description }}
                                </p>
                                <p
                                    v-if="promotion.conditions"
                                    class="mt-2 text-xs text-muted-foreground"
                                >
                                    {{ promotion.conditions }}
                                </p>
                            </article>
                        </div>
                    </section>

                    <section
                        v-if="listing.packages.length > 0"
                        class="space-y-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Paquetes</h2>
                            <p class="text-sm text-muted-foreground">
                                Opciones de renta con duración y capacidad
                                incluidas.
                            </p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <article
                                v-for="packageItem in listing.packages"
                                :key="packageItem.id"
                                class="flex flex-col rounded-xl border bg-card p-4"
                            >
                                <h3 class="font-semibold">
                                    {{ packageItem.name }}
                                </h3>
                                <p class="mt-1 text-xl font-semibold">
                                    {{ packageItem.price_formatted }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ packageItem.duration_hours }} horas ·
                                    {{ packageItem.included_people }} personas
                                </p>
                                <p
                                    v-if="packageItem.description"
                                    class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                                >
                                    {{ packageItem.description }}
                                </p>
                                <div
                                    v-if="packageItem.amenities.length > 0"
                                    class="mt-3 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="amenity in packageItem.amenities"
                                        :key="amenity.key"
                                        variant="secondary"
                                    >
                                        {{ amenity.label }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="packageItem.services.length > 0"
                                    class="mt-2 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="service in packageItem.services"
                                        :key="service.key"
                                        variant="outline"
                                    >
                                        {{ service.label }}
                                    </Badge>
                                </div>
                                <p
                                    v-if="packageItem.conditions"
                                    class="mt-3 text-xs text-muted-foreground"
                                >
                                    {{ packageItem.conditions }}
                                </p>
                            </article>
                        </div>
                    </section>

                    <section
                        v-if="listing.commercialServices.length > 0"
                        class="space-y-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">
                                Servicios adicionales
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Servicios incluidos o disponibles con costo
                                extra.
                            </p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <article
                                v-for="service in listing.commercialServices"
                                :key="service.id"
                                class="rounded-xl border bg-card p-4"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">
                                        {{ service.label }}
                                    </h3>
                                    <Badge
                                        :variant="
                                            service.is_included
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            service.is_included
                                                ? 'Incluido'
                                                : 'Costo adicional'
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    v-if="service.has_price"
                                    class="mt-2 text-sm font-medium"
                                >
                                    {{ service.price_formatted }} ·
                                    {{ service.price_unit_label }}
                                </p>
                                <p
                                    v-else
                                    class="mt-2 text-sm text-muted-foreground"
                                >
                                    Precio no especificado
                                </p>
                                <p
                                    v-if="service.description"
                                    class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                                >
                                    {{ service.description }}
                                </p>
                            </article>
                        </div>
                    </section>

                    <section
                        v-if="listing.serviceGroups.length > 0"
                        class="space-y-4"
                    >
                        <h2 class="text-lg font-semibold">Características</h2>
                        <div
                            v-for="group in listing.serviceGroups"
                            :key="group.key"
                            class="space-y-2"
                        >
                            <h3
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{ group.label }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="service in group.services"
                                    :key="service.value"
                                    variant="secondary"
                                >
                                    {{ service.label }}
                                </Badge>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="listing.details.length > 0"
                        class="space-y-3"
                    >
                        <h2 class="text-lg font-semibold">Detalles</h2>
                        <dl class="grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="detail in listing.details"
                                :key="detail.key"
                                class="rounded-lg border p-3"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ detail.label }}
                                </dt>
                                <dd class="text-sm font-medium">
                                    {{ detail.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="listing.rules" class="space-y-2">
                        <h2 class="text-lg font-semibold">Reglas</h2>
                        <p class="whitespace-pre-line text-muted-foreground">
                            {{ listing.rules }}
                        </p>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-lg font-semibold">Ubicación</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ listing.address }}
                            <span v-if="listing.city">
                                · {{ listing.city }}, {{ listing.state }}
                            </span>
                        </p>
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
                            height="24rem"
                        />
                    </section>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-6 lg:h-fit">
                    <div class="space-y-4 rounded-xl border bg-card p-5">
                        <div
                            v-if="
                                listing.account.logo_url || listing.account.name
                            "
                            class="flex items-center gap-3 border-b pb-3"
                        >
                            <img
                                v-if="listing.account.logo_url"
                                :src="listing.account.logo_url"
                                :alt="listing.account.name ?? listing.name"
                                class="size-10 rounded-md border object-cover"
                            />
                            <div>
                                <p
                                    class="text-xs text-muted-foreground uppercase"
                                >
                                    Anfitrión
                                </p>
                                <p class="font-medium">
                                    {{ listing.account.name }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <h2 class="font-semibold">Contacta al anfitrión</h2>
                            <p class="text-sm text-muted-foreground">
                                La reserva y el anticipo se acuerdan
                                directamente.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Button as-child class="w-full">
                                <Link
                                    :href="`/terrazas/${listing.slug}/disponibilidad`"
                                >
                                    Consultar disponibilidad
                                </Link>
                            </Button>
                            <Button v-if="whatsappUrl" as-child class="w-full">
                                <a
                                    :href="whatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Abrir WhatsApp
                                </a>
                            </Button>
                            <Button
                                v-if="contactEmail"
                                variant="outline"
                                as-child
                                class="w-full"
                            >
                                <a :href="`mailto:${contactEmail}`">
                                    Enviar correo
                                </a>
                            </Button>
                            <Button
                                v-if="contactPhone"
                                variant="outline"
                                as-child
                                class="w-full"
                            >
                                <a :href="`tel:${contactPhone}`"> Llamar </a>
                            </Button>
                        </div>

                        <p
                            v-if="listing.contact_name"
                            class="text-xs text-muted-foreground"
                        >
                            Contacto: {{ listing.contact_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            WhatsApp abre una conversación manual; la plataforma
                            no envía el mensaje.
                        </p>
                    </div>

                    <div
                        v-if="unavailableDates.length > 0"
                        class="space-y-3 rounded-xl border bg-card p-5"
                    >
                        <h2 class="font-semibold">
                            Próximas fechas no disponibles
                        </h2>
                        <ul class="space-y-1 text-sm">
                            <li
                                v-for="date in unavailableDates"
                                :key="date.date"
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="text-muted-foreground">
                                    {{ date.date }}
                                </span>
                                <span class="text-xs">{{ date.label }}</span>
                            </li>
                        </ul>
                        <Button variant="outline" as-child class="w-full">
                            <Link
                                :href="`/terrazas/${listing.slug}/disponibilidad`"
                            >
                                Ver calendario completo
                            </Link>
                        </Button>
                    </div>
                </aside>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                ¿Este anuncio parece fraudulento o incorrecto?
                <Link :href="urls.report" class="underline">
                    Reportar terraza
                </Link>
            </p>
        </div>
    </PublicShell>
</template>
