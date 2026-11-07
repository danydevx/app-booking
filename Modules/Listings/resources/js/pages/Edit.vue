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
    ServiceOption,
} from '../types';

const props = defineProps<{
    listing: ListingFormData;
    services: ServiceOption[];
    detailFields: Record<string, ListingDetailField>;
    limits: ListingLimits;
    canPublish: boolean;
    canDelete: boolean;
    urls: {
        index: string;
        update: string;
        publish: string;
        unpublish: string;
        destroy: string;
        media_store: string;
        media_reorder: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Terrazas',
                href: '/',
            },
            {
                title: 'Editar terraza',
            },
        ],
    },
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

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
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

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.index">Volver</Link>
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
                <Button
                    v-if="canPublish && listing.status !== 'published'"
                    @click="publish"
                >
                    Publicar
                </Button>
                <Button
                    v-if="canPublish && listing.status === 'published'"
                    variant="outline"
                    @click="unpublish"
                >
                    Despublicar
                </Button>
                <Button v-if="canDelete" variant="destructive" @click="destroy">
                    Eliminar
                </Button>
            </div>
        </div>

        <div class="rounded-xl border bg-card p-5 sm:p-6">
            <ListingForm
                mode="edit"
                :listing="listing"
                :services="services"
                :detail-fields="detailFields"
                :urls="urls"
            />

            <MediaManager
                :media="listing.media"
                :limits="limits"
                :urls="urls"
            />
        </div>
    </div>
</template>
