<script setup lang="ts">
import '@/homeSite';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AvailabilityCalendar from '../../components/AvailabilityCalendar.vue';
import InquiryForm from '../../components/InquiryForm.vue';
import type { BlockedDate, PublicListingSummary } from '../../types';

const props = defineProps<{
    listing: PublicListingSummary;
    blocked_dates: BlockedDate[];
    attribution: Record<string, string>;
    submitted: string | null;
    urls: {
        index: string;
        listing: string;
        store: string;
    };
}>();

const cover = computed(
    () => props.listing.media.find((media) => media.type === 'image') ?? null,
);

const location = computed(
    () =>
        [props.listing.city, props.listing.state].filter(Boolean).join(', ') ||
        'Ubicación por confirmar',
);
</script>

<template>
    <Head :title="`Disponibilidad · ${listing.name}`" />

    <div class="home-container py-8 sm:py-10">
        <Link
            :href="urls.listing"
            class="text-sm font-semibold text-[color:var(--home-secondary)] hover:text-[color:var(--home-primary-deep)]"
        >
            ← Volver a la ficha
        </Link>

        <header class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-3xl">
                <p class="home-eyebrow">Disponibilidad</p>
                <h1
                    class="mt-2 text-3xl leading-tight font-extrabold sm:text-4xl"
                >
                    {{ listing.name }}
                </h1>
                <p class="mt-2 text-base text-[color:var(--home-secondary)]">
                    {{ location }}
                    <span v-if="listing.capacity">
                        · Hasta {{ listing.capacity }} personas
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link :href="urls.listing" class="home-btn home-btn--outline">
                    Ver ficha completa
                </Link>
                <Link :href="urls.index" class="home-btn home-btn--outline">
                    Explorar más terrazas
                </Link>
            </div>
        </header>

        <div
            v-if="submitted"
            class="mt-6 rounded-[1rem] border border-[#A7F3D0] bg-[#ECFDF5] p-4 text-sm font-medium text-[#065F46]"
            role="status"
        >
            {{ submitted }}
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="min-w-0 space-y-8">
                <div
                    v-if="cover"
                    class="home-media aspect-[16/9] overflow-hidden rounded-[1.125rem]"
                >
                    <img
                        :src="cover.url"
                        :alt="cover.alt_text ?? listing.name"
                        fetchpriority="high"
                    />
                </div>

                <section
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Calendario</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Fechas disponibles
                    </h2>
                    <p class="mt-2 text-sm text-[color:var(--home-muted)]">
                        Las fechas marcadas no están disponibles. Enviar una
                        solicitud no bloquea el calendario.
                    </p>
                    <div class="mt-5">
                        <AvailabilityCalendar
                            :blocked-dates="blocked_dates"
                            :months="3"
                            variant="public"
                        />
                    </div>
                </section>

                <section
                    v-if="listing.services.length > 0"
                    class="border-t border-[color:var(--home-border)] pt-8"
                >
                    <p class="home-eyebrow">Características</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Lo que ofrece el lugar
                    </h2>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span
                            v-for="service in listing.services"
                            :key="service.key"
                            class="home-chip"
                        >
                            {{ service.label }}
                        </span>
                    </div>
                </section>
            </div>

            <aside class="min-w-0 lg:self-start">
                <div
                    class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                >
                    <InquiryForm
                        :store-url="urls.store"
                        :listing-name="listing.name"
                        :attribution="attribution"
                    />
                </div>
            </aside>
        </div>
    </div>
</template>
