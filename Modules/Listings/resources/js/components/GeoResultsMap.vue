<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { GeoBounds, GeoMapPoint } from '../types';

const props = withDefaults(
    defineProps<{
        points: GeoMapPoint[];
        center?: { lat: number; lng: number } | null;
        radius?: number | null;
        bounds?: GeoBounds | null;
        selectedId?: number | null;
        height?: string;
    }>(),
    {
        center: null,
        radius: null,
        bounds: null,
        selectedId: null,
        height: '100%',
    },
);

const emit = defineEmits<{
    (event: 'select', id: number): void;
    (event: 'bounds-change', bounds: GeoBounds): void;
}>();

const mapElement = ref<HTMLElement | null>(null);

let map: L.Map | null = null;
let layer: L.LayerGroup | null = null;
const selectedMarker: { current: L.CircleMarker | null } = { current: null };
const currentMarker = (): L.CircleMarker | null => selectedMarker.current;
let programmaticUntil = 0;
let fittedSignature = '';

const mexicoCenter: [number, number] = [23.6345, -102.5528];

onMounted(() => {
    if (!mapElement.value) {
        return;
    }

    map = L.map(mapElement.value, {
        scrollWheelZoom: true,
        zoomControl: true,
    }).setView(mexicoCenter, 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    layer = L.layerGroup().addTo(map);

    map.on('moveend', handleMoveEnd);
    map.on('zoomend', () => renderMarkers());

    applyGeoView();
});

watch(
    () => props.points,
    () => applyGeoView(),
);

watch(
    () => props.bounds,
    () => applyGeoView(),
    { deep: true },
);

watch(
    () => [props.center?.lat, props.center?.lng, props.radius],
    () => applyGeoView(),
);

watch(
    () => props.selectedId,
    () => focusSelected(),
);

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    layer = null;
    selectedMarker.current = null;
});

function signature(): string {
    if (props.bounds) {
        return `b:${props.bounds.north}:${props.bounds.south}:${props.bounds.east}:${props.bounds.west}`;
    }

    if (props.center) {
        return `c:${props.center.lat}:${props.center.lng}:${props.radius ?? ''}`;
    }

    return `p:${props.points
        .map((point) => `${point.id}:${point.latitude}:${point.longitude}`)
        .join('|')}`;
}

function applyGeoView(): void {
    if (!map) {
        return;
    }

    const nextSignature = signature();

    if (nextSignature === fittedSignature) {
        renderMarkers();

        return;
    }

    fittedSignature = nextSignature;
    programmaticUntil = Date.now() + 900;

    if (props.bounds) {
        map.fitBounds(
            [
                [props.bounds.south, props.bounds.west],
                [props.bounds.north, props.bounds.east],
            ],
            { padding: [16, 16], animate: false },
        );
    } else if (props.center) {
        map.setView(
            [props.center.lat, props.center.lng],
            props.radius ? 11 : 12,
            { animate: false },
        );
    } else {
        const coordinates = props.points
            .filter(
                (point) => point.latitude !== null && point.longitude !== null,
            )
            .map(
                (point) =>
                    [point.latitude as number, point.longitude as number] as [
                        number,
                        number,
                    ],
            );

        if (coordinates.length === 1) {
            map.setView(coordinates[0], 13, { animate: false });
        } else if (coordinates.length > 1) {
            map.fitBounds(L.latLngBounds(coordinates), {
                padding: [30, 30],
                maxZoom: 12,
                animate: false,
            });
        }
    }

    renderMarkers();
}

function handleMoveEnd(): void {
    if (!map || Date.now() < programmaticUntil) {
        return;
    }

    const bounds = map.getBounds();

    emit('bounds-change', {
        north: bounds.getNorth(),
        south: bounds.getSouth(),
        east: bounds.getEast(),
        west: bounds.getWest(),
    });
}

function renderMarkers(): void {
    if (!map || !layer) {
        return;
    }

    layer.clearLayers();
    selectedMarker.current = null;

    if (props.center && props.radius) {
        L.circle([props.center.lat, props.center.lng], {
            radius: props.radius * 1000,
            color: '#f50557',
            fillColor: '#f50557',
            fillOpacity: 0.06,
            weight: 1,
        }).addTo(layer);
    }

    const selected = props.points.find(
        (point) => point.id === props.selectedId,
    );
    const zoom = map.getZoom();

    if (zoom >= 14) {
        for (const point of props.points) {
            drawPoint(point, point.id === props.selectedId);
        }

        return;
    }

    const groups = new Map<
        string,
        { x: number; y: number; points: GeoMapPoint[] }
    >();

    for (const point of props.points) {
        if (point.latitude === null || point.longitude === null) {
            continue;
        }

        if (point.id === props.selectedId) {
            continue;
        }

        const projected = map.project([point.latitude, point.longitude], zoom);
        const key = `${Math.floor(projected.x / 70)}:${Math.floor(projected.y / 70)}`;
        const group = groups.get(key) ?? {
            x: 0,
            y: 0,
            points: [] as GeoMapPoint[],
        };

        group.x += projected.x;
        group.y += projected.y;
        group.points.push(point);
        groups.set(key, group);
    }

    for (const group of groups.values()) {
        const count = group.points.length;

        if (count === 1) {
            drawPoint(group.points[0], false);

            continue;
        }

        const position = map.unproject(
            L.point(group.x / count, group.y / count),
            zoom,
        );

        drawCluster(position, count);
    }

    if (selected) {
        drawPoint(selected, true);
        currentMarker()?.openPopup();
    }
}

function drawPoint(point: GeoMapPoint, selected: boolean): void {
    if (!map || !layer || point.latitude === null || point.longitude === null) {
        return;
    }

    const marker = L.circleMarker([point.latitude, point.longitude], {
        radius: selected ? 11 : 8,
        color: '#ffffff',
        weight: selected ? 3 : 2,
        fillColor: selected ? '#f50557' : '#1f2937',
        fillOpacity: 1,
    })
        .addTo(layer)
        .bindPopup(popupContent(point), { minWidth: 190 });

    marker.on('click', () => emit('select', point.id));

    if (selected) {
        selectedMarker.current = marker;
    }
}

function drawCluster(position: L.LatLng, count: number): void {
    if (!map || !layer) {
        return;
    }

    const marker = L.marker(position, {
        icon: L.divIcon({
            className: 'geo-cluster',
            html: `<span style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:9999px;background:#f50557;color:#fff;font-weight:700;font-size:0.8125rem;border:3px solid #ffffff;box-shadow:0 8px 18px -8px rgba(36,29,23,.6)">${count}</span>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
        }),
    }).addTo(layer);

    marker.on('click', () => {
        const zoom = map?.getZoom() ?? 8;
        map?.flyTo(position, Math.min(zoom + 2, 16), { duration: 0.4 });
    });
}

function popupContent(point: GeoMapPoint): HTMLElement {
    const wrapper = document.createElement('div');
    wrapper.className = 'space-y-1';

    const title = document.createElement('a');
    title.href = point.url;
    title.className = 'pub-display text-base leading-tight';
    title.style.color = '#111827';
    title.style.textDecoration = 'none';
    title.textContent = point.name;
    wrapper.append(title);

    const place = [point.city, point.state].filter(Boolean).join(', ');

    if (place !== '') {
        const meta = document.createElement('p');
        meta.className = 'text-xs';
        meta.style.color = '#6b7280';
        meta.textContent = place;
        wrapper.append(meta);
    }

    if (point.price_from_formatted) {
        const price = document.createElement('p');
        price.className = 'text-sm font-medium';
        price.textContent = `Desde ${point.price_from_formatted}`;
        wrapper.append(price);
    }

    const link = document.createElement('a');
    link.href = point.url;
    link.className = 'text-sm font-semibold';
    link.style.color = '#c90345';
    link.style.textDecoration = 'underline';
    link.textContent = 'Ver detalle';
    wrapper.append(link);

    return wrapper;
}

function focusSelected(): void {
    if (!map || props.selectedId === null) {
        return;
    }

    const point = props.points.find(
        (candidate) => candidate.id === props.selectedId,
    );

    if (!point || point.latitude === null || point.longitude === null) {
        return;
    }

    programmaticUntil = Date.now() + 900;
    map.flyTo([point.latitude, point.longitude], Math.max(map.getZoom(), 14), {
        duration: 0.45,
    });

    window.setTimeout(() => {
        if (!map) {
            return;
        }

        renderMarkers();
        currentMarker()?.openPopup();
    }, 520);
}

function invalidate(): void {
    map?.invalidateSize();
}

function refresh(): void {
    if (!map) {
        return;
    }

    map.invalidateSize();
    fittedSignature = '';
    programmaticUntil = Date.now() + 900;
    applyGeoView();
}

defineExpose({ invalidate, refresh });
</script>

<template>
    <div
        ref="mapElement"
        class="z-0 h-full w-full"
        :style="{ height: height }"
        role="img"
        aria-label="Mapa de terrazas"
    />
</template>
