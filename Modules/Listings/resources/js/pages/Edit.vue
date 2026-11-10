<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ListingForm from '../components/ListingForm.vue';
import MediaManager from '../components/MediaManager.vue';
import type {
    ListingDetailField,
    ListingFormData,
    ListingLimits,
    ListingLocations,
    ServiceGroup,
} from '../types';

const props = defineProps<{
    listing: ListingFormData;
    serviceGroups: ServiceGroup[];
    detailFields: Record<string, ListingDetailField>;
    locations: ListingLocations;
    limits: ListingLimits;
    canPublish: boolean;
    canDelete: boolean;
    urls: {
        index: string;
        manage: string;
        edit: string;
        bookings: string;
        customers: string;
        availability: string;
        update: string;
        publish: string;
        unpublish: string;
        destroy: string;
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
            bookings: string;
            customers: string;
            availability: string;
            update: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Fotos y datos' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Reservaciones', href: props.urls.bookings },
            { title: 'Clientes', href: props.urls.customers },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Fotos y datos', href: props.urls.edit },
        ],
    }),
});

function publish(): void {
    router.patch(props.urls.publish, {}, { preserveScroll: true });
}

function unpublish(): void {
    router.patch(props.urls.unpublish, {}, { preserveScroll: true });
}

function destroy(): void {
    if (!window.confirm('¿Eliminar esta terraza y todos sus archivos?')) {
        return;
    }

    router.delete(props.urls.destroy);
}
</script>

<template>
    <Head :title="`Editar ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ listing.name }}</h1>
                    <Badge
                        :variant="
                            listing.status === 'published'
                                ? 'default'
                                : listing.status === 'suspended'
                                  ? 'destructive'
                                  : 'secondary'
                        "
                    >
                        {{ listing.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Edita la información, administra los archivos y publica
                    cuando esté lista.
                </p>
            </div>

            <Button variant="outline" as-child>
                <Link :href="urls.index">Volver</Link>
            </Button>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-5 sm:p-6">
                    <ListingForm
                        mode="edit"
                        :listing="listing"
                        :service-groups="serviceGroups"
                        :detail-fields="detailFields"
                        :locations="locations"
                        :urls="urls"
                    />
                </div>

                <div class="rounded-xl border bg-card p-5 sm:p-6">
                    <MediaManager
                        :media="listing.media"
                        :limits="limits"
                        :urls="urls"
                    />
                </div>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-6">
                <div class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Publicación</h2>
                    <div class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">Estado</span>
                            <Badge
                                :variant="
                                    listing.status === 'published'
                                        ? 'default'
                                        : listing.status === 'suspended'
                                          ? 'destructive'
                                          : 'secondary'
                                "
                            >
                                {{ listing.status_label }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground">
                            {{
                                listing.status === 'published'
                                    ? 'La terraza es visible en el catálogo público.'
                                    : 'Completa la información y publica para que sea visible.'
                            }}
                        </p>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <Button
                            v-if="canPublish && listing.status !== 'published'"
                            type="button"
                            @click="publish"
                        >
                            Publicar terraza
                        </Button>
                        <Button
                            v-if="canPublish && listing.status === 'published'"
                            type="button"
                            variant="outline"
                            @click="unpublish"
                        >
                            Despublicar
                        </Button>
                        <Button
                            v-if="listing.public_url"
                            variant="outline"
                            as-child
                        >
                            <a
                                :href="listing.public_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Ver página pública
                            </a>
                        </Button>
                    </div>
                </div>

                <div
                    v-if="canDelete"
                    class="rounded-xl border border-destructive/40 bg-destructive/5 p-5"
                >
                    <h2 class="font-semibold">Zona de riesgo</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Eliminar la terraza borra también sus fotos y videos.
                    </p>
                    <Button
                        class="mt-4"
                        type="button"
                        variant="destructive"
                        @click="destroy"
                    >
                        Eliminar terraza
                    </Button>
                </div>
            </aside>
        </div>
    </div>
</template>
