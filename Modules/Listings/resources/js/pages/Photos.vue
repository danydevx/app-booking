<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Camera, ImagePlus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import MediaManager from '../components/MediaManager.vue';
import type { ListingLimits, ListingMedia } from '../types';

const props = defineProps<{
    listing: {
        id: number;
        name: string;
        slug: string;
        status: string;
        status_label: string;
        public_url: string | null;
    };
    media: ListingMedia[];
    limits: ListingLimits;
    urls: {
        index: string;
        manage: string;
        edit: string;
        photos: string;
        pricing: string;
        packages: string;
        services: string;
        promotions: string;
        bookings: string;
        customers: string;
        availability: string;
        media_store: string;
        media_reorder: string;
    };
}>();

defineOptions({
    layout: (props: {
        listing: { name: string };
        urls: {
            index: string;
            manage: string;
            edit: string;
            photos: string;
            pricing: string;
            packages: string;
            services: string;
            promotions: string;
            bookings: string;
            customers: string;
            availability: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Fotos y videos' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Reservaciones', href: props.urls.bookings },
            { title: 'Clientes', href: props.urls.customers },
            { title: 'Precios', href: props.urls.pricing },
            { title: 'Paquetes', href: props.urls.packages },
            { title: 'Servicios', href: props.urls.services },
            { title: 'Promociones', href: props.urls.promotions },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Datos', href: props.urls.edit },
            { title: 'Fotos', href: props.urls.photos },
        ],
    }),
});
</script>

<template>
    <Head :title="`Fotos · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Camera class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">Fotos y videos</h1>
                        <Badge
                            :variant="
                                listing.status === 'published'
                                    ? 'default'
                                    : 'secondary'
                            "
                        >
                            {{ listing.status_label }}
                        </Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Administra los archivos de {{ listing.name }}. La
                        primera imagen será la portada de la ficha pública.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.manage">Volver al resumen</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="urls.edit">Editar datos</Link>
                </Button>
                <Button v-if="listing.public_url" variant="outline" as-child>
                    <a
                        :href="listing.public_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver pública
                    </a>
                </Button>
            </div>
        </header>

        <div class="rounded-xl border bg-card p-5 sm:p-6">
            <MediaManager :media="media" :limits="limits" :urls="urls" />
        </div>

        <p
            class="flex items-start gap-2 rounded-lg border border-dashed p-3 text-xs text-muted-foreground"
        >
            <ImagePlus class="mt-0.5 size-4 shrink-0" />
            Los archivos se guardan en el almacenamiento público. Usa imágenes
            recientes y de buena calidad; la portada es la primera de la lista.
        </p>
    </div>
</template>
