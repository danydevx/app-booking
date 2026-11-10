<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ServiceOption } from '../../types';

type Promotion = {
    id: number;
    title: string;
    description: string | null;
    promotional_price: string | null;
    previous_price: string | null;
    promotional_price_formatted: string | null;
    previous_price_formatted: string | null;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    applies_on: string[];
    applies_on_keys: string[];
    conditions: string | null;
    active: boolean;
    status: { value: string; label: string };
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    listing: { id: number; name: string; slug: string };
    promotions: Promotion[];
    weekdays: ServiceOption[];
    urls: {
        index: string;
        manage: string;
        edit: string;
        pricing: string;
        packages: string;
        services: string;
        promotions: string;
        availability: string;
        public: string | null;
        store: string;
    };
}>();

defineOptions({
    layout: (props: {
        listing: { name: string };
        urls: {
            index: string;
            manage: string;
            edit: string;
            pricing: string;
            packages: string;
            services: string;
            promotions: string;
            availability: string;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Terrazas', href: props.urls.index },
            { title: props.listing.name, href: props.urls.manage },
            { title: 'Promociones' },
        ],
        submenu: [
            { title: 'Resumen', href: props.urls.manage },
            { title: 'Precios', href: props.urls.pricing },
            { title: 'Paquetes', href: props.urls.packages },
            { title: 'Servicios', href: props.urls.services },
            { title: 'Promociones', href: props.urls.promotions },
            { title: 'Disponibilidad', href: props.urls.availability },
            { title: 'Fotos y datos', href: props.urls.edit },
        ],
    }),
});

const form = useForm({
    title: '',
    description: '',
    promotional_price: '',
    previous_price: '',
    starts_at: '',
    ends_at: '',
    applies_on: [] as string[],
    conditions: '',
    active: true,
});

const editing = ref<Promotion | null>(null);

function toggleDay(day: string): void {
    form.applies_on = form.applies_on.includes(day)
        ? form.applies_on.filter((item) => item !== day)
        : [...form.applies_on, day];
}

function submit(): void {
    if (editing.value) {
        form.patch(editing.value.update_url, {
            preserveScroll: true,
            onSuccess: reset,
        });

        return;
    }

    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: reset,
    });
}

function edit(item: Promotion): void {
    editing.value = item;
    form.title = item.title;
    form.description = item.description ?? '';
    form.promotional_price = item.promotional_price ?? '';
    form.previous_price = item.previous_price ?? '';
    form.starts_at = item.starts_at;
    form.ends_at = item.ends_at;
    form.applies_on = [...item.applies_on_keys];
    form.conditions = item.conditions ?? '';
    form.active = item.active;
    form.clearErrors();
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.active = true;
}

function destroy(item: Promotion): void {
    if (!window.confirm(`¿Eliminar la promoción ${item.title}?`)) {
        return;
    }

    router.delete(item.delete_url, { preserveScroll: true });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'current') {
        return 'default';
    }

    if (status === 'expired') {
        return 'destructive';
    }

    if (status === 'future') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head :title="`Promociones · ${listing.name}`" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Promociones</h1>
                <p class="text-sm text-muted-foreground">
                    Promociones informativas de {{ listing.name }}. No modifican
                    precios ni cotizaciones automáticamente.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.manage">Volver al resumen</Link>
            </Button>
        </div>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,25rem)_minmax(0,1fr)]"
        >
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">
                    {{ editing ? 'Editar promoción' : 'Nueva promoción' }}
                </h2>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="promotion-title">Título</Label>
                        <Input
                            id="promotion-title"
                            v-model="form.title"
                            maxlength="120"
                            required
                            placeholder="Promoción entre semana"
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="promotion-description">Descripción</Label>
                        <textarea
                            id="promotion-description"
                            v-model="form.description"
                            rows="3"
                            maxlength="2000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="promotion-price"
                                >Precio promocional</Label
                            >
                            <Input
                                id="promotion-price"
                                v-model="form.promotional_price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="promotion-previous"
                                >Precio anterior</Label
                            >
                            <Input
                                id="promotion-previous"
                                v-model="form.previous_price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="promotion-start">Inicio</Label>
                            <Input
                                id="promotion-start"
                                v-model="form.starts_at"
                                type="date"
                                required
                            />
                            <p
                                v-if="form.errors.starts_at"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.starts_at }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="promotion-end">Fin</Label>
                            <Input
                                id="promotion-end"
                                v-model="form.ends_at"
                                type="date"
                                required
                            />
                            <p
                                v-if="form.errors.ends_at"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.ends_at }}
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label>Días aplicables</Label>
                        <div class="flex flex-wrap gap-3">
                            <label
                                v-for="day in weekdays"
                                :key="day.value"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    :checked="
                                        form.applies_on.includes(day.value)
                                    "
                                    @update:checked="toggleDay(day.value)"
                                />
                                {{ day.label }}
                            </label>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="promotion-conditions">Condiciones</Label>
                        <textarea
                            id="promotion-conditions"
                            v-model="form.conditions"
                            rows="3"
                            maxlength="2000"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <label class="flex items-center gap-3 text-sm">
                        <Checkbox v-model:checked="form.active" />
                        Promoción activa
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{
                                editing ? 'Guardar cambios' : 'Crear promoción'
                            }}
                        </Button>
                        <Button
                            v-if="editing"
                            type="button"
                            variant="secondary"
                            @click="reset"
                        >
                            Cancelar
                        </Button>
                    </div>
                </form>
            </section>

            <section class="space-y-3">
                <div
                    v-if="promotions.length === 0"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No hay promociones configuradas.
                </div>

                <article
                    v-for="item in promotions"
                    :key="item.id"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">{{ item.title }}</h2>
                                <Badge
                                    :variant="statusVariant(item.status.value)"
                                >
                                    {{ item.status.label }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ item.starts_at_label }} al
                                {{ item.ends_at_label }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="edit(item)"
                            >
                                Editar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="destroy(item)"
                            >
                                Eliminar
                            </Button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        <span
                            v-if="item.promotional_price_formatted"
                            class="font-medium"
                        >
                            {{ item.promotional_price_formatted }}
                        </span>
                        <span
                            v-if="item.previous_price_formatted"
                            class="text-muted-foreground line-through"
                        >
                            {{ item.previous_price_formatted }}
                        </span>
                    </div>
                    <p
                        v-if="item.applies_on.length > 0"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{ item.applies_on.join(' · ') }}
                    </p>
                    <p
                        v-if="item.description"
                        class="mt-3 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ item.description }}
                    </p>
                    <p
                        v-if="item.conditions"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{ item.conditions }}
                    </p>
                </article>
            </section>
        </div>
    </div>
</template>
