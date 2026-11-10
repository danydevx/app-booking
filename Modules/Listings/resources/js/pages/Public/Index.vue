<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PaginationLinks from '@/components/PaginationLinks.vue';
import PublicShell from '@/components/PublicShell.vue';
import ListingMap from '../../components/ListingMap.vue';
import type {
    ListingLocations,
    ListingPublicCard,
    Paginated,
    PublicFilters,
    ServiceOption,
} from '../../types';

type PublicSearchForm = {
    state: string;
    city: string;
    capacity: string | number;
    service: string;
    lat: string | number;
    lng: string | number;
    radius: string | number;
};

const props = defineProps<{
    listings: Paginated<ListingPublicCard>;
    center: { lat: number; lng: number } | null;
    radius: number | null;
    filters: PublicFilters;
    priorityServices: ServiceOption[];
    additionalServices: ServiceOption[];
    locations: ListingLocations;
    urls: {
        index: string;
    };
}>();

const form = reactive<PublicSearchForm>({
    state: props.filters.state ?? '',
    city: props.filters.city ?? '',
    capacity: props.filters.capacity ?? '',
    service: props.filters.service ?? '',
    lat: props.filters.lat ?? '',
    lng: props.filters.lng ?? '',
    radius: props.filters.radius ?? 25,
});

const stateOptions = computed(() =>
    Object.entries(props.locations).map(([value, state]) => ({
        value,
        label: state.label,
    })),
);

const municipalityOptions = computed(() => {
    const municipalities = props.locations[form.state]?.municipalities ?? [];
    const options = municipalities.map((municipality) => ({
        value: municipality,
        label: municipality,
    }));

    if (form.city && !municipalities.includes(form.city)) {
        options.unshift({
            value: form.city,
            label: `${form.city} (valor actual)`,
        });
    }

    return options;
});

watch(
    () => form.state,
    (state, previousState) => {
        if (state === previousState) {
            return;
        }

        const municipalities = props.locations[state]?.municipalities ?? [];

        if (form.city !== '' && !municipalities.includes(form.city)) {
            form.city = '';
        }
    },
);

function search(): void {
    router.get(props.urls.index, { ...form }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Explorar terrazas" />

    <PublicShell>
        <div class="space-y-6">
            <div class="max-w-2xl space-y-2">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Encuentra una terraza para tu próximo evento
                </h1>
                <p class="text-muted-foreground">
                    Busca por estado, municipio o radio de distancia. Los
                    resultados se actualizan en el mapa.
                </p>
            </div>

            <form
                class="grid gap-4 rounded-xl border bg-card p-4 sm:p-5 lg:grid-cols-8"
                @submit.prevent="search"
            >
                <div class="grid gap-2">
                    <Label for="state">Estado</Label>
                    <Select v-model="form.state">
                        <SelectTrigger id="state" class="w-full">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="state in stateOptions"
                                :key="state.value"
                                :value="state.value"
                            >
                                {{ state.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2 lg:col-span-2">
                    <Label for="city">Municipio / alcaldía</Label>
                    <Select
                        v-model="form.city"
                        :disabled="municipalityOptions.length === 0"
                    >
                        <SelectTrigger id="city" class="w-full">
                            <SelectValue
                                :placeholder="
                                    form.state
                                        ? 'Selecciona municipio'
                                        : 'Selecciona estado'
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="municipality in municipalityOptions"
                                :key="municipality.value"
                                :value="municipality.value"
                            >
                                {{ municipality.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="capacity">Capacidad mínima</Label>
                    <Input
                        id="capacity"
                        v-model.number="form.capacity"
                        type="number"
                        min="1"
                        placeholder="50"
                    />
                </div>
                <div class="grid gap-2 lg:col-span-2">
                    <Label for="service">Servicio</Label>
                    <select
                        id="service"
                        v-model="form.service"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="">Cualquier característica</option>
                        <optgroup label="Destacadas">
                            <option
                                v-for="service in priorityServices"
                                :key="service.value"
                                :value="service.value"
                            >
                                {{ service.label }}
                            </option>
                        </optgroup>
                        <optgroup label="Más características">
                            <option
                                v-for="service in additionalServices"
                                :key="service.value"
                                :value="service.value"
                            >
                                {{ service.label }}
                            </option>
                        </optgroup>
                    </select>
                </div>
                <div class="flex items-end lg:col-span-2">
                    <Button type="submit" class="w-full">
                        <Search />
                        Buscar
                    </Button>
                </div>

                <div class="grid gap-2 sm:grid-cols-3 lg:col-span-8">
                    <div class="grid gap-2">
                        <Label for="lat">Latitud</Label>
                        <Input
                            id="lat"
                            v-model="form.lat"
                            inputmode="decimal"
                            placeholder="19.4326"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="lng">Longitud</Label>
                        <Input
                            id="lng"
                            v-model="form.lng"
                            inputmode="decimal"
                            placeholder="-99.1332"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="radius">Radio (km)</Label>
                        <Input
                            id="radius"
                            v-model.number="form.radius"
                            type="number"
                            min="1"
                            max="100"
                        />
                    </div>
                </div>
            </form>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium">
                            {{ listings.total }}
                            {{ listings.total === 1 ? 'terraza' : 'terrazas' }}
                        </p>
                    </div>

                    <div
                        v-if="listings.data.length === 0"
                        class="rounded-xl border border-dashed p-10 text-center"
                    >
                        <h2 class="font-medium">No encontramos terrazas</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Prueba con otra ciudad, un radio mayor o menos
                            filtros.
                        </p>
                    </div>

                    <article
                        v-for="listing in listings.data"
                        :key="listing.id"
                        class="flex flex-col overflow-hidden rounded-xl border bg-card sm:flex-row"
                    >
                        <div
                            class="aspect-video bg-muted sm:aspect-auto sm:w-52"
                        >
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
                                Sin imagen
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div>
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <h2 class="font-semibold">
                                        <Link
                                            :href="listing.url"
                                            class="hover:underline"
                                        >
                                            {{ listing.name }}
                                        </Link>
                                    </h2>
                                    <span
                                        v-if="listing.distance_km !== null"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ listing.distance_km }} km
                                    </span>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        [listing.city, listing.state]
                                            .filter(Boolean)
                                            .join(', ') ||
                                        'Ubicación por confirmar'
                                    }}
                                    <span v-if="listing.capacity">
                                        · Hasta {{ listing.capacity }} personas
                                    </span>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="service in listing.services.slice(
                                        0,
                                        5,
                                    )"
                                    :key="service"
                                    variant="secondary"
                                >
                                    {{ service }}
                                </Badge>
                                <Badge
                                    v-if="listing.services.length > 5"
                                    variant="outline"
                                >
                                    +{{ listing.services.length - 5 }}
                                </Badge>
                            </div>

                            <div class="mt-auto">
                                <Button size="sm" as-child>
                                    <Link :href="listing.url">Ver detalle</Link>
                                </Button>
                            </div>
                        </div>
                    </article>

                    <PaginationLinks :links="listings.links" />
                </div>

                <div class="lg:sticky lg:top-6 lg:h-fit">
                    <ListingMap
                        :listings="listings.data"
                        :center="center"
                        :radius="radius"
                        height="32rem"
                    />
                </div>
            </div>
        </div>
    </PublicShell>
</template>
