<script setup lang="ts">
import { MapPin, Navigation } from '@lucide/vue';
import { computed, defineAsyncComponent } from 'vue';
import GuidedSearchStep from './GuidedSearchStep.vue';
import type { GuidedLocation } from '../../composables/useGuidedSearch';

type MunicipalityCenter = {
    state: string;
    city: string;
    latitude: number;
    longitude: number;
    listings_count: number;
};

const GuidedLocationMap = defineAsyncComponent(
    () => import('./GuidedLocationMap.vue'),
);

const props = defineProps<{
    locations: Record<string, { label: string; municipalities: string[] }>;
    municipalityCenters: MunicipalityCenter[];
    location: GuidedLocation;
}>();

const emit = defineEmits<{
    'update:location': [value: GuidedLocation];
}>();

const radiusOptions = [5, 10, 15, 25, 50];

const centerMap = computed(
    () =>
        new Map(
            props.municipalityCenters.map((center) => [
                `${center.state}||${center.city}`,
                center,
            ]),
        ),
);

const locationGroups = computed(() =>
    Object.entries(props.locations).map(([state, data]) => ({
        state,
        label: data.label,
        cities: data.municipalities.map((city) => ({
            value: `${state}||${city}`,
            label: city,
        })),
    })),
);

const selectedValue = computed(() => {
    if (!props.location.state || !props.location.city) {
        return '';
    }

    return `${props.location.state}||${props.location.city}`;
});

const hasCenter = computed(
    () => props.location.latitude !== null && props.location.longitude !== null,
);

const mapCenter = computed(() =>
    hasCenter.value
        ? {
              lat: props.location.latitude as number,
              lng: props.location.longitude as number,
          }
        : null,
);

const defaultCenter = computed(() => {
    if (props.municipalityCenters.length === 0) {
        return null;
    }

    const total = props.municipalityCenters.reduce(
        (carry, center) => ({
            lat: carry.lat + center.latitude,
            lng: carry.lng + center.longitude,
        }),
        { lat: 0, lng: 0 },
    );

    return {
        lat: total.lat / props.municipalityCenters.length,
        lng: total.lng / props.municipalityCenters.length,
    };
});

const selectedCityHasCenter = computed(
    () =>
        selectedValue.value !== '' && centerMap.value.has(selectedValue.value),
);

function onMunicipalityChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;

    if (value === '') {
        emit('update:location', {
            state: null,
            city: null,
            latitude: null,
            longitude: null,
            radiusKm: null,
        });

        return;
    }

    const [state, city] = value.split('||');
    const center = centerMap.value.get(value);

    emit('update:location', {
        state,
        city,
        latitude: center?.latitude ?? null,
        longitude: center?.longitude ?? null,
        radiusKm: center ? (props.location.radiusKm ?? 10) : null,
    });
}

function onMapCenter(center: { lat: number; lng: number }): void {
    emit('update:location', {
        state: null,
        city: null,
        latitude: center.lat,
        longitude: center.lng,
        radiusKm: props.location.radiusKm ?? 10,
    });
}

function setRadius(radius: number): void {
    emit('update:location', {
        ...props.location,
        radiusKm: radius,
    });
}

function clearLocation(): void {
    emit('update:location', {
        state: null,
        city: null,
        latitude: null,
        longitude: null,
        radiusKm: null,
    });
}
</script>

<template>
    <GuidedSearchStep
        title="¿Dónde quieres celebrar tu evento?"
        subtitle="Elige una zona y hasta dónde estás dispuesto a desplazarte. También puedes marcar un punto directamente en el mapa."
    >
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <div class="space-y-5">
                <div>
                    <label class="home-field-label" for="guided-location">
                        Municipio o zona
                    </label>
                    <select
                        id="guided-location"
                        class="home-control"
                        :value="selectedValue"
                        @change="onMunicipalityChange"
                    >
                        <option value="">Cualquier zona de la cobertura</option>
                        <optgroup
                            v-for="group in locationGroups"
                            :key="group.state"
                            :label="group.label"
                        >
                            <option
                                v-for="city in group.cities"
                                :key="city.value"
                                :value="city.value"
                            >
                                {{ city.label }}
                            </option>
                        </optgroup>
                    </select>
                    <p class="mt-2 text-xs text-[color:var(--home-muted)]">
                        Solo aparecen municipios dentro de la cobertura
                        habilitada por Booking.
                    </p>
                </div>

                <div>
                    <p class="home-field-label">Radio de búsqueda</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="radius in radiusOptions"
                            :key="radius"
                            type="button"
                            class="home-chip transition-colors"
                            :class="
                                location.radiusKm === radius
                                    ? 'border-[color:var(--home-primary)] bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]'
                                    : 'hover:border-[color:var(--home-primary)]/40'
                            "
                            :aria-pressed="location.radiusKm === radius"
                            :disabled="!hasCenter"
                            @click="setRadius(radius)"
                        >
                            {{ radius }} km
                        </button>
                    </div>
                    <p
                        v-if="!hasCenter"
                        class="mt-2 text-xs text-[color:var(--home-muted)]"
                    >
                        Selecciona un municipio con coordenadas o marca un punto
                        en el mapa para aplicar un radio.
                    </p>
                    <p
                        v-else
                        class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-[color:var(--home-secondary)]"
                    >
                        <Navigation class="size-3.5" aria-hidden="true" />
                        Radio de {{ location.radiusKm ?? 10 }} km
                    </p>
                </div>

                <div
                    v-if="selectedCityHasCenter"
                    class="flex items-start gap-2 rounded-[0.875rem] bg-[color:var(--home-bg-alt)] p-3 text-xs text-[color:var(--home-secondary)]"
                >
                    <MapPin
                        class="mt-0.5 size-4 shrink-0 text-[color:var(--home-primary-deep)]"
                        aria-hidden="true"
                    />
                    Usamos el centro aproximado de la zona seleccionada. Puedes
                    ajustarlo haciendo clic en el mapa.
                </div>

                <button
                    v-if="hasCenter || selectedValue !== ''"
                    type="button"
                    class="text-sm font-semibold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                    @click="clearLocation"
                >
                    Quitar ubicación
                </button>
            </div>

            <GuidedLocationMap
                :center="mapCenter"
                :radius="location.radiusKm"
                :default-center="defaultCenter"
                height="23rem"
                @update:center="onMapCenter"
            />
        </div>
    </GuidedSearchStep>
</template>
