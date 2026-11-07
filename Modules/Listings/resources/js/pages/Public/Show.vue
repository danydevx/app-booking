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
