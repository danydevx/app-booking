<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin, Users } from '@lucide/vue';
import type { GuidedResult } from '../../composables/useGuidedSearch';

defineProps<{
    listing: GuidedResult;
}>();

const emit = defineEmits<{
    contact: [listing: GuidedResult];
}>();

function locationLabel(listing: GuidedResult): string {
    return (
        [listing.city, listing.state].filter(Boolean).join(', ') ||
        'Ubicación por confirmar'
    );
}
</script>

<template>
    <article
        class="overflow-hidden rounded-[1.125rem] border border-[color:var(--home-border)] bg-white sm:grid sm:grid-cols-[16rem_minmax(0,1fr)]"
    >
        <div
            class="relative aspect-[4/3] bg-[color:var(--home-soft)] sm:aspect-auto"
        >
            <img
                v-if="listing.cover_url"
                :src="listing.cover_url"
                :alt="listing.name"
                class="absolute inset-0 h-full w-full object-cover"
                loading="lazy"
                decoding="async"
            />
            <div
                v-else
                class="flex h-full min-h-48 items-center justify-center px-4 text-center text-sm font-semibold text-[color:var(--home-primary-deep)]"
            >
                Sin fotografía disponible
            </div>
        </div>

        <div class="flex min-w-0 flex-col p-5">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="text-xl leading-tight font-extrabold">
                        {{ listing.name }}
                    </h3>
                    <p
                        class="mt-1 flex items-center gap-1.5 text-sm text-[color:var(--home-secondary)]"
                    >
                        <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                        {{ locationLabel(listing) }}
                    </p>
                </div>
                <p
                    v-if="listing.price_from_formatted"
                    class="shrink-0 text-sm font-bold text-[color:var(--home-primary-deep)]"
                >
                    Desde {{ listing.price_from_formatted }}
                </p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span
                    v-if="listing.capacity"
                    class="home-chip inline-flex items-center gap-1"
                >
                    <Users class="size-3.5" aria-hidden="true" />
                    Hasta {{ listing.capacity }} personas
                </span>
                <span
                    v-if="listing.distance_km !== null"
                    class="home-chip inline-flex items-center gap-1"
                >
                    {{ listing.distance_km }} km
                </span>
            </div>

            <div
                v-if="listing.services.length > 0"
                class="mt-3 flex flex-wrap gap-1.5"
            >
                <span
                    v-for="service in listing.services.slice(0, 5)"
                    :key="service"
                    class="home-chip"
                >
                    {{ service }}
                </span>
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-2 pt-5">
                <Link
                    :href="listing.url"
                    class="home-btn home-btn--outline home-btn--sm"
                >
                    Ver detalles
                </Link>
                <button
                    type="button"
                    class="home-btn home-btn--primary home-btn--sm"
                    @click="emit('contact', listing)"
                >
                    Contactar
                </button>
            </div>
        </div>
    </article>
</template>
