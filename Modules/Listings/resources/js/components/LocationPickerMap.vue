<script setup lang="ts">
import { X } from '@lucide/vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        latitude: string | number | null;
        longitude: string | number | null;
        height?: string;
    }>(),
    {
        height: '20rem',
    },
);

const emit = defineEmits<{
    change: [latitude: string, longitude: string];
}>();

const mapElement = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const mexicoCenter: [number, number] = [23.6345, -102.5528];

const pinIcon = L.divIcon({
    className: '',
    html: '<div style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:#0f766e;border:2px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.35)"><span style="width:8px;height:8px;border-radius:9999px;background:#fff"></span></div>',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
});

function toCoordinate(value: string | number | null): number | null {
    if (value === null || value === '') {
        return null;
    }

    const coordinate = Number(value);

    return Number.isFinite(coordinate) ? coordinate : null;
}

function currentCoordinates(): { lat: number; lng: number } | null {
    const lat = toCoordinate(props.latitude);
    const lng = toCoordinate(props.longitude);

    if (
        lat === null ||
        lng === null ||
        lat < -90 ||
        lat > 90 ||
        lng < -180 ||
        lng > 180
    ) {
        return null;
    }

    return { lat, lng };
}

const coordinateLabel = computed(() => {
    const coordinates = currentCoordinates();

    if (!coordinates) {
        return null;
    }

    return `${coordinates.lat.toFixed(6)}, ${coordinates.lng.toFixed(6)}`;
});

function round(value: number): string {
    return value.toFixed(6);
}

function emitCoordinates(latlng: L.LatLng): void {
    emit('change', round(latlng.lat), round(latlng.lng));
}

function placeMarker(latlng: L.LatLng): void {
    if (!map) {
        return;
    }

    if (!marker) {
        marker = L.marker(latlng, {
            draggable: true,
            icon: pinIcon,
            keyboard: true,
        }).addTo(map);

        marker.on('dragend', () => {
            const position = marker?.getLatLng();

            if (position) {
                emitCoordinates(position);
            }
        });

        return;
    }

    marker.setLatLng(latlng);
}

function clearLocation(): void {
    marker?.remove();
    marker = null;
    map?.setView(mexicoCenter, 5);
    emit('change', '', '');
}

onMounted(() => {
    if (!mapElement.value) {
        return;
    }

    const coordinates = currentCoordinates();

    map = L.map(mapElement.value, {
        scrollWheelZoom: false,
        zoomControl: true,
    }).setView(
        coordinates ? [coordinates.lat, coordinates.lng] : mexicoCenter,
        coordinates ? 14 : 5,
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    if (coordinates) {
        placeMarker(L.latLng(coordinates.lat, coordinates.lng));
    }

    map.on('click', (event: L.LeafletMouseEvent) => {
        placeMarker(event.latlng);
        emitCoordinates(event.latlng);
    });
});

watch(
    () => [props.latitude, props.longitude],
    () => {
        if (!map) {
            return;
        }

        const coordinates = currentCoordinates();

        if (!coordinates) {
            marker?.remove();
            marker = null;

            return;
        }

        const hadMarker = marker !== null;
        placeMarker(L.latLng(coordinates.lat, coordinates.lng));

        if (!hadMarker && map.getZoom() < 12) {
            map.setView([coordinates.lat, coordinates.lng], 14);

            return;
        }

        map.panTo([coordinates.lat, coordinates.lng]);
    },
);

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="space-y-2">
        <div class="relative">
            <div
                ref="mapElement"
                class="z-0 overflow-hidden rounded-xl border"
                :style="{ height }"
                role="region"
                aria-label="Mapa para seleccionar la ubicación de la terraza"
            />
            <div
                class="pointer-events-none absolute bottom-3 left-3 rounded-md bg-background/90 px-2 py-1 text-xs text-muted-foreground shadow-sm"
            >
                Arrastra el pin o haz clic en el mapa
            </div>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
        >
            <span>
                {{
                    coordinateLabel
                        ? `Ubicación: ${coordinateLabel}`
                        : 'Sin ubicación seleccionada'
                }}
            </span>
            <Button
                v-if="coordinateLabel"
                type="button"
                size="sm"
                variant="ghost"
                @click="clearLocation"
            >
                <X class="size-3.5" />
                Limpiar ubicación
            </Button>
        </div>
    </div>
</template>
