<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Tags } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type EventTypeRow = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    icon: string | null;
    is_active: boolean;
    sort_order: number;
    listings_count: number;
};

type CharacteristicRow = {
    id: number;
    service_key: string;
    name: string;
    description: string | null;
    icon: string | null;
    is_featured: boolean;
    sort_order: number;
    listings_count: number;
};

const props = defineProps<{
    eventTypes: EventTypeRow[];
    characteristics: CharacteristicRow[];
    urls: {
        update: string;
        dashboard: string;
    };
}>();

const form = useForm<{
    event_types: Array<{
        id: number;
        name: string;
        description: string;
        icon: string;
        is_active: boolean;
        sort_order: number | string;
    }>;
    characteristics: Array<{
        id: number;
        description: string;
        icon: string;
        is_featured: boolean;
        sort_order: number | string;
    }>;
}>({
    event_types: props.eventTypes.map((eventType) => ({
        id: eventType.id,
        name: eventType.name,
        description: eventType.description ?? '',
        icon: eventType.icon ?? '',
        is_active: eventType.is_active,
        sort_order: eventType.sort_order,
    })),
    characteristics: props.characteristics.map((characteristic) => ({
        id: characteristic.id,
        description: characteristic.description ?? '',
        icon: characteristic.icon ?? '',
        is_featured: characteristic.is_featured,
        sort_order: characteristic.sort_order,
    })),
});

const activeEventTypes = computed(
    () => form.event_types.filter((eventType) => eventType.is_active).length,
);

const featuredCharacteristics = computed(
    () =>
        form.characteristics.filter(
            (characteristic) => characteristic.is_featured,
        ).length,
);

function save(): void {
    form.transform((data) => ({
        event_types: data.event_types.map((eventType) => ({
            ...eventType,
            description: eventType.description || null,
            icon: eventType.icon || null,
            sort_order: Number(eventType.sort_order) || 0,
        })),
        characteristics: data.characteristics.map((characteristic) => ({
            ...characteristic,
            description: characteristic.description || null,
            icon: characteristic.icon || null,
            sort_order: Number(characteristic.sort_order) || 0,
        })),
    })).patch(props.urls.update, {
        preserveScroll: true,
    });
}

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}
</script>

<template>
    <Head title="Catálogos" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div
                    class="flex items-center gap-2 text-sm font-medium text-primary"
                >
                    <Tags class="size-4" />
                    Configuración
                </div>
                <h1 class="mt-1 text-2xl font-semibold">Catálogos</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Administra los tipos de evento y las características
                    destacadas que los propietarios pueden seleccionar y que los
                    visitantes usan para filtrar el catálogo.
                </p>
            </div>

            <Button :disabled="form.processing" @click="save">
                <Save class="size-4" />
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </Button>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
            role="alert"
        >
            Revisa los campos marcados antes de guardar.
        </div>

        <div
            class="grid gap-3 rounded-xl border bg-muted/20 p-4 sm:grid-cols-3"
        >
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Tipos de evento activos
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ activeEventTypes }}
                </p>
            </div>
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Características destacadas
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ featuredCharacteristics }}
                </p>
            </div>
            <div class="flex items-end">
                <p class="text-xs text-muted-foreground">
                    Solo el Superadmin puede modificar estos catálogos.
                </p>
            </div>
        </div>

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Tipos de evento</h2>
                    <p class="text-sm text-muted-foreground">
                        Desactiva una opción para ocultarla de los formularios y
                        de los filtros públicos. Las relaciones existentes se
                        conservan.
                    </p>
                </div>
            </div>

            <article
                v-for="(eventType, index) in form.event_types"
                :key="eventType.id"
                class="rounded-xl border bg-card p-4"
            >
                <div
                    class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1.8fr)_6rem_6rem_auto] lg:items-end"
                >
                    <div class="grid gap-1.5">
                        <Label :for="`event-name-${eventType.id}`"
                            >Nombre</Label
                        >
                        <Input
                            :id="`event-name-${eventType.id}`"
                            v-model="eventType.name"
                            maxlength="80"
                            :aria-invalid="
                                Boolean(errorFor(`event_types.${index}.name`))
                            "
                        />
                        <p
                            v-if="errorFor(`event_types.${index}.name`)"
                            class="text-xs text-destructive"
                        >
                            {{ errorFor(`event_types.${index}.name`) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ eventTypes[index]?.slug }} ·
                            {{ eventTypes[index]?.listings_count ?? 0 }}
                            {{
                                eventTypes[index]?.listings_count === 1
                                    ? 'terraza'
                                    : 'terrazas'
                            }}
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label :for="`event-description-${eventType.id}`">
                            Descripción
                        </Label>
                        <Input
                            :id="`event-description-${eventType.id}`"
                            v-model="eventType.description"
                            maxlength="255"
                            placeholder="Descripción breve para el propietario"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label :for="`event-icon-${eventType.id}`">Icono</Label>
                        <Input
                            :id="`event-icon-${eventType.id}`"
                            v-model="eventType.icon"
                            maxlength="50"
                            placeholder="heart"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label :for="`event-order-${eventType.id}`">
                            Orden
                        </Label>
                        <Input
                            :id="`event-order-${eventType.id}`"
                            v-model="eventType.sort_order"
                            type="number"
                            min="0"
                            max="999"
                            inputmode="numeric"
                        />
                    </div>

                    <label
                        class="flex items-center gap-2 pb-2 text-sm font-medium"
                    >
                        <Checkbox v-model="eventType.is_active" />
                        Activo
                    </label>
                </div>
            </article>
        </section>

        <section class="space-y-3 border-t pt-6">
            <div>
                <h2 class="text-lg font-semibold">
                    Características destacadas
                </h2>
                <p class="text-sm text-muted-foreground">
                    Elige qué características del catálogo aparecen como
                    tarjetas rápidas en el buscador. Su nombre proviene del
                    catálogo de instalaciones para evitar valores duplicados.
                </p>
            </div>

            <article
                v-for="(characteristic, index) in form.characteristics"
                :key="characteristic.id"
                class="rounded-xl border bg-card p-4"
            >
                <div
                    class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_6rem_6rem_auto] lg:items-end"
                >
                    <div class="grid gap-1.5">
                        <p class="text-sm font-semibold">
                            {{ characteristics[index]?.name }}
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge variant="outline">
                                {{ characteristics[index]?.service_key }}
                            </Badge>
                            <Badge variant="secondary">
                                {{
                                    characteristics[index]?.listings_count ?? 0
                                }}
                                terrazas
                            </Badge>
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label
                            :for="`characteristic-description-${characteristic.id}`"
                        >
                            Descripción
                        </Label>
                        <Input
                            :id="`characteristic-description-${characteristic.id}`"
                            v-model="characteristic.description"
                            maxlength="255"
                            placeholder="Texto breve para la tarjeta"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label
                            :for="`characteristic-icon-${characteristic.id}`"
                        >
                            Icono
                        </Label>
                        <Input
                            :id="`characteristic-icon-${characteristic.id}`"
                            v-model="characteristic.icon"
                            maxlength="50"
                            placeholder="trees"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label
                            :for="`characteristic-order-${characteristic.id}`"
                        >
                            Orden
                        </Label>
                        <Input
                            :id="`characteristic-order-${characteristic.id}`"
                            v-model="characteristic.sort_order"
                            type="number"
                            min="0"
                            max="999"
                            inputmode="numeric"
                        />
                    </div>

                    <label
                        class="flex items-center gap-2 pb-2 text-sm font-medium"
                    >
                        <Checkbox v-model="characteristic.is_featured" />
                        Destacada
                    </label>
                </div>
            </article>
        </section>

        <div class="flex justify-end">
            <Button :disabled="form.processing" @click="save">
                <Save class="size-4" />
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </Button>
        </div>
    </div>
</template>
