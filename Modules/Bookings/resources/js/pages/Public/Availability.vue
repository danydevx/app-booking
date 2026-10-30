<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AvailabilityCalendar from '../../components/AvailabilityCalendar.vue';
import InquiryForm from '../../components/InquiryForm.vue';
import type { BlockedDate, PublicListingSummary } from '../../types';

const props = defineProps<{
    listing: PublicListingSummary;
    blocked_dates: BlockedDate[];
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
</script>

<template>
    <Head :title="`Disponibilidad · ${listing.name}`" />

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
                    <Button variant="outline" as-child>
                        <Link :href="urls.listing">Ver ficha completa</Link>
                    </Button>
                </div>
            </div>

            <div
                v-if="submitted"
                class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                {{ submitted }}
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-6">
                    <div
                        v-if="cover"
                        class="overflow-hidden rounded-xl border bg-muted"
                    >
                        <img
                            :src="cover.url"
                            :alt="cover.alt_text ?? listing.name"
                            class="aspect-video w-full object-cover"
                        />
                    </div>

                    <section class="space-y-3">
                        <h2 class="text-lg font-semibold">Disponibilidad</h2>
                        <p class="text-sm text-muted-foreground">
                            Las fechas marcadas no están disponibles. Enviar una
                            solicitud no bloquea el calendario.
                        </p>
                        <AvailabilityCalendar
                            :blocked-dates="blocked_dates"
                            :months="3"
                        />
                    </section>

                    <section
                        v-if="listing.services.length > 0"
                        class="space-y-3"
                    >
                        <h2 class="text-lg font-semibold">Servicios</h2>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="service in listing.services"
                                :key="service.key"
                                variant="secondary"
                            >
                                {{ service.label }}
                            </Badge>
                        </div>
                    </section>
                </div>

                <aside class="lg:sticky lg:top-6 lg:h-fit">
                    <div class="rounded-xl border bg-card p-5">
                        <InquiryForm
                            :store-url="urls.store"
                            :listing-name="listing.name"
                        />
                    </div>
                </aside>
            </div>
        </div>
    </PublicShell>
</template>
