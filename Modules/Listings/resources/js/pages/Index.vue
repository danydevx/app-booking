<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import type {
    ListingLimits,
    ListingOwnerCard,
    ListingStatus,
    Paginated,
} from '../types';

defineProps<{
    team: {
        name: string;
        slug: string;
    };
    listings: Paginated<ListingOwnerCard>;
    limits: ListingLimits;
    canCreate: boolean;
    plan: {
        name: string;
        slug: string;
        listing_limit: number;
    };
    urls: {
        index: string;
        create: string;
        billing: string;
    };
}>();

defineOptions({
    layout: (props: { urls: { index: string } }) => ({
        breadcrumbs: [
            {
                title: 'Terrazas',
                href: props.urls.index,
            },
        ],
    }),
});

function statusVariant(
    status: ListingStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'published') {
        return 'default';
    }

    if (status === 'suspended') {
        return 'destructive';
    }

    return 'secondary';
}
</script>

<template>
    <Head title="Terrazas" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Terrazas</h1>
                <p class="text-sm text-muted-foreground">
                    Administra los espacios publicados por {{ team.name }}.
                </p>
            </div>

            <Button v-if="canCreate" as-child>
                <Link :href="urls.create">
                    <Plus />
                    Nueva terraza
                </Link>
            </Button>
        </div>

        <div
            v-if="!canCreate"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
        >
            <div>
                <p class="font-medium">Límite del plan {{ plan.name }}</p>
                <p>
                    La cuenta permite hasta {{ plan.listing_limit }}
                    {{ plan.listing_limit === 1 ? 'terraza' : 'terrazas' }}. Tus
                    terrazas actuales no se eliminan.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.billing">Ver planes</Link>
            </Button>
        </div>

        <div
            v-if="listings.data.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-10 text-center"
        >
            <h2 class="text-lg font-medium">Aún no hay terrazas</h2>
            <p class="max-w-md text-sm text-muted-foreground">
                Crea tu primera terraza, agrega fotos y publica para que los
                clientes puedan encontrarla.
            </p>
            <Button v-if="canCreate" as-child>
                <Link :href="urls.create">Crear terraza</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="listing in listings.data"
                :key="listing.id"
                class="overflow-hidden rounded-xl border bg-card"
            >
                <div class="aspect-video bg-muted">
                    <img
                        v-if="listing.cover_url"
                        :src="listing.cover_url"
                        :alt="listing.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full items-center justify-center text-sm text-muted-foreground"
                    >
                        Sin portada
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ listing.name }}</h2>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    [listing.city, listing.state]
                                        .filter(Boolean)
                                        .join(', ') || 'Sin ubicación'
                                }}
                            </p>
                        </div>
                        <Badge :variant="statusVariant(listing.status)">
                            {{ listing.status_label }}
                        </Badge>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        {{ listing.images_count }} imágenes ·
                        {{ listing.videos_count }} videos
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Button size="sm" as-child>
                            <Link :href="listing.manage_url">Administrar</Link>
                        </Button>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="listing.edit_url">Editar</Link>
                        </Button>
                        <Button
                            v-if="listing.public_url"
                            size="sm"
                            variant="outline"
                            as-child
                        >
                            <a
                                :href="listing.public_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Ver pública
                            </a>
                        </Button>
                    </div>
                </div>
            </article>
        </div>

        <PaginationLinks :links="listings.links" />
    </div>
</template>
