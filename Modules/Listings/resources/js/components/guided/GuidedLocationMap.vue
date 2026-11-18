<script setup lang="ts">
import type { LayerGroup, LeafletMouseEvent, Map as LeafletMap } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

type LeafletModule = typeof import('leaflet');
let L = null as unknown as LeafletModule;

const props = withDefaults(
    defineProps<{
        center: { lat: number; lng: number } | null;
        radius: number | null;
        defaultCenter?: { lat: number; lng: number } | null;
        height?: string;
    }>(),
    {
        defaultCenter: null,
        height: '24rem',
    },
);

const emit = defineEmits<{
    'update:center': [value: { lat: number; lng: number }];
}>();

const mapElement = ref<HTMLElement | null>(null);

let map: LeafletMap | null = null;
let layer: LayerGroup | null = null;

const mexicoCenter: [number, number] = [23.6345, -102.5528];

onMounted(async () => {
    L = (await import('leaflet')).default as unknown as LeafletModule;

    if (!mapElement.value) {
        return;
    }

    const start = props.center ??
        props.defaultCenter ?? {
            lat: mexicoCenter[0],
            lng: mexicoCenter[1],
        };

    map = L.map(mapElement.value, {
        scrollWheelZoom: true,
        zoomControl: true,
    }).setView([start.lat, start.lng], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    layer = L.layerGroup().addTo(map);
    map.on('click', handleClick);
    render();
});

watch(
    () => [props.center?.lat, props.center?.lng, props.radius],
    () => {
        if (map && props.center) {
            map.setView([props.center.lat, props.center.lng], map.getZoom(), {
                animate: false,
            });
        }

        render();
    },
);

onBeforeUnmount(() => {
    map?.off('click', handleClick);
    map?.remove();
    map = null;
    layer = null;
});

function handleClick(event: LeafletMouseEvent): void {
    emit('update:center', {
        lat: Number(event.latlng.lat.toFixed(6)),
        lng: Number(event.latlng.lng.toFixed(6)),
    });
}

function primaryColor(): string {
    const element = mapElement.value;

    if (!element) {
        return '#f50557';
    }

    const color = window
        .getComputedStyle(element)
        .getPropertyValue('--home-primary')
        .trim();

    return color !== '' ? color : '#f50557';
}

function render(): void {
    if (!map || !layer) {
        return;
    }

    layer.clearLayers();

    if (!props.center) {
        return;
    }

    const center = L.latLng(props.center.lat, props.center.lng);
    const color = primaryColor();

    L.circleMarker(center, {
        radius: 8,
        color: '#ffffff',
        fillColor: color,
        fillOpacity: 1,
        weight: 2,
    })
        .bindPopup('Centro de búsqueda')
        .addTo(layer);

    if (props.radius !== null) {
        L.circle(center, {
            radius: props.radius * 1000,
            color,
            fillColor: color,
            fillOpacity: 0.07,
            weight: 1.5,
        }).addTo(layer);
    }
}
</script>

<template>
    <div>
        <div
            ref="mapElement"
            class="z-0 overflow-hidden rounded-[1.125rem] border border-[color:var(--home-border)]"
            :style="{ height }"
            role="region"
            aria-label="Mapa para seleccionar el centro de búsqueda"
        />
        <p class="mt-2 text-xs text-[color:var(--home-muted)]">
            Haz clic en el mapa para mover el centro de búsqueda. También puedes
            elegir un municipio en el selector.
        </p>
    </div>
</template>
