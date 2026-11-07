<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PaginationLinks from '@/components/PaginationLinks.vue';

type ListingRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    city: string | null;
    team_name: string | null;
    team_slug: string | null;
    images_count: number;
    videos_count: number;
    cover_url: string | null;
    is_suspended: boolean;
    suspended_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    listings: Paginated<ListingRow>;
    filters: { q: string; status: string | null };
    statuses: Array<{ value: string; label: string }>;
    urls: {
        index: string;
        dashboard: string;
    };
}>();

const search = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');

function submit(): void {
    router.get(
        props.urls.index,
        {
            q: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function statusVariant(
    value: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (value === 'published') {
        return 'default';
    }

    if (value === 'suspended') {
        return 'destructive';
    }

    return 'secondary';
}
</script>

<template>
    <Head title="Terrazas · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Terrazas</h1>
                <p class="text-sm text-muted-foreground">
                    Revisa el contenido publicado y suspende terrazas con
                    motivo.
                </p>
            </div>

            <form
                class="flex w-full max-w-xl flex-col gap-2 sm:flex-row"
                @submit.prevent="submit"
            >
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por terraza, ciudad o cuenta…"
                    aria-label="Buscar terraza"
                    class="flex-1"
                />
                <select
                    v-model="status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    aria-label="Estado"
                >
                    <option value="">Todos los estados</option>
                    <option
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <Button type="submit" variant="outline">Filtrar</Button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead
                    class="bg-muted/50 text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Terraza</th>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Medios</th>
                        <th class="px-4 py-3">Alta</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="listing in listings.data" :key="listing.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-12 shrink-0 overflow-hidden rounded-md bg-muted"
                                >
                                    <img
                                        v-if="listing.cover_url"
                                        :src="listing.cover_url"
                                        :alt="listing.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div>
                                    <p class="font-medium">
                                        {{ listing.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ listing.city ?? 'Sin ciudad' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{ listing.team_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(listing.status)">
                                {{ listing.status_label }}
                            </Badge>
                            <Badge
                                v-if="listing.is_suspended"
                                variant="destructive"
                                class="ml-1"
                            >
                                Moderación
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{ listing.images_count }} fotos ·
                            {{ listing.videos_count }} videos
                        </td>
                        <td class="px-4 py-3">
                            {{ listing.created_at ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`${urls.index}/${listing.slug}`">
                                    Revisar
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="listings.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No se encontraron terrazas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ listings.from ?? 0 }}–{{ listings.to ?? 0 }} de
                {{ listings.total }}
            </p>
            <PaginationLinks :links="listings.links" />
        </div>
    </div>
</template>
