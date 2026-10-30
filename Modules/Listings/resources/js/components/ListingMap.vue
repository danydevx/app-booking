<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type MapListing = {
    id: number;
    name: string;
    url: string;
    latitude: number | null;
    longitude: number | null;
    distance_km: number | null;
};

const props = defineProps<{
    listings: MapListing[];
    center?: { lat: number; lng: number } | null;
    radius?: number | null;
    height?: string;
    zoom?: number;
}>();

const mapElement = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let markers: L.LayerGroup | null = null;

const mexicoCenter: [number, number] = [23.6345, -102.5528];

onMounted(() => {
    if (!mapElement.value) {
        return;
    }

    map = L.map(mapElement.value).setView(
        props.center ? [props.center.lat, props.center.lng] : mexicoCenter,
        props.zoom ?? 11,
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    markers = L.layerGroup().addTo(map);
    render();
});

watch(
    () => [props.listings, props.center, props.radius],
    () => render(),
    { deep: true },
);

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    markers = null;
});

function render(): void {
    if (!map || !markers) {
        return;
    }

    markers.clearLayers();

    const bounds = L.latLngBounds([]);

    if (props.center) {
        const center = L.latLng(props.center.lat, props.center.lng);
        bounds.extend(center);

        L.circleMarker(center, {
            radius: 7,
            color: '#0f766e',
            fillColor: '#0f766e',
            fillOpacity: 1,
            weight: 2,
        })
            .bindPopup('Centro de búsqueda')
            .addTo(markers);

        if (props.radius) {
            L.circle(center, {
                radius: props.radius * 1000,
                color: '#0f766e',
                fillColor: '#0f766e',
                fillOpacity: 0.06,
                weight: 1,
            }).addTo(markers);
        }
    }

    for (const listing of props.listings) {
        if (listing.latitude === null || listing.longitude === null) {
            continue;
        }

        const position = L.latLng(listing.latitude, listing.longitude);
        bounds.extend(position);

        const popup = document.createElement('div');
        const link = document.createElement('a');
        link.href = listing.url;
        link.className =
            'font-medium text-primary underline-offset-4 hover:underline';
        link.textContent = listing.name;
        popup.append(link);

        if (listing.distance_km !== null) {
            const distance = document.createElement('p');
            distance.className = 'mt-1 text-xs text-muted-foreground';
            distance.textContent = `${listing.distance_km} km de distancia`;
            popup.append(distance);
        }

        L.circleMarker(position, {
            radius: 8,
            color: '#1d4ed8',
            fillColor: '#3b82f6',
            fillOpacity: 0.9,
            weight: 2,
        })
            .bindPopup(popup)
            .addTo(markers);
    }

    if (bounds.isValid()) {
        map.fitBounds(bounds.pad(0.2), { maxZoom: 14 });
    }
}
</script>

<template>
    <div
        ref="mapElement"
        class="z-0 overflow-hidden rounded-xl border"
        :style="{ height: height ?? '26rem' }"
        role="img"
        aria-label="Mapa de terrazas"
    />
</template>
