<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Tag, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { z } from 'zod';
import { useFrontValidation } from '@/composables/useFrontValidation';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RequiredFieldsHint from '@/components/RequiredFieldsHint.vue';
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
        photos: string;
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
            photos: string;
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
            { title: 'Datos', href: props.urls.edit },
            { title: 'Fotos', href: props.urls.photos },
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
const dialogOpen = ref(false);

const promotionSchema = z
    .object({
        title: z
            .string()
            .trim()
            .min(1, 'El título es obligatorio.')
            .max(120, 'El título no puede superar 120 caracteres.'),
        description: z
            .string()
            .max(2000, 'La descripción no puede superar 2000 caracteres.'),
        promotional_price: z
            .string()
            .refine(
                (value) =>
                    value === '' ||
                    (Number.isFinite(Number(value)) &&
                        Number(value) >= 0 &&
                        Number(value) <= 10000000),
                'Indica un precio válido.',
            ),
        previous_price: z
            .string()
            .refine(
                (value) =>
                    value === '' ||
                    (Number.isFinite(Number(value)) &&
                        Number(value) >= 0 &&
                        Number(value) <= 10000000),
                'Indica un precio válido.',
            ),
        starts_at: z.string().min(1, 'La fecha de inicio es obligatoria.'),
        ends_at: z.string().min(1, 'La fecha de fin es obligatoria.'),
        applies_on: z.array(z.string()),
        conditions: z
            .string()
            .max(2000, 'Las condiciones no pueden superar 2000 caracteres.'),
        active: z.boolean(),
    })
    .refine(
        (values) =>
            !values.starts_at ||
            !values.ends_at ||
            values.ends_at >= values.starts_at,
        {
            path: ['ends_at'],
            message: 'La fecha final debe ser igual o posterior al inicio.',
        },
    );

const { validate } = useFrontValidation(promotionSchema, () => ({
    title: form.title,
    description: form.description,
    promotional_price: form.promotional_price,
    previous_price: form.previous_price,
    starts_at: form.starts_at,
    ends_at: form.ends_at,
    applies_on: form.applies_on,
    conditions: form.conditions,
    active: form.active,
}));

function toggleDay(day: string): void {
    form.applies_on = form.applies_on.includes(day)
        ? form.applies_on.filter((item) => item !== day)
        : [...form.applies_on, day];
}

function openCreate(): void {
    reset();
    dialogOpen.value = true;
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
    dialogOpen.value = true;
}

function handleDialogOpen(open: boolean): void {
    dialogOpen.value = open;

    if (!open) {
        reset();
    }
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.active = true;
}

async function submit(): Promise<void> {
    if (!(await validate(form))) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            reset();
        },
    };

    if (editing.value) {
        form.patch(editing.value.update_url, options);

        return;
    }

    form.post(props.urls.store, options);
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
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Tag class="size-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">Promociones</h1>
                        <Badge variant="outline">Informativo</Badge>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Promociones de {{ listing.name }}. No modifican precios
                        ni cotizaciones automáticamente.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.manage">Volver al resumen</Link>
                </Button>
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" />
                    Nueva promoción
                </Button>
            </div>
        </header>

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">
                        Promociones configuradas
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Vigencia, días aplicables y condiciones.
                    </p>
                </div>
                <Badge variant="secondary">
                    {{ promotions.length }}
                    {{ promotions.length === 1 ? 'promoción' : 'promociones' }}
                </Badge>
            </div>

            <div
                v-if="promotions.length === 0"
                class="rounded-xl border border-dashed p-10 text-center"
            >
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <Tag class="size-6" />
                </div>
                <p class="mt-3 font-medium">Sin promociones configuradas</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Crea una promoción para destacar una oferta por fechas.
                </p>
                <Button
                    type="button"
                    class="mt-4"
                    variant="outline"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    Crear primera promoción
                </Button>
            </div>

            <div v-else class="grid items-start gap-4 xl:grid-cols-2">
                <article
                    v-for="item in promotions"
                    :key="item.id"
                    class="flex flex-col overflow-hidden rounded-xl border bg-card"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ item.title }}</h3>
                                <Badge
                                    :variant="statusVariant(item.status.value)"
                                >
                                    {{ item.status.label }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ item.starts_at_label }} al
                                {{ item.ends_at_label }}
                            </p>
                        </div>
                        <p
                            v-if="item.promotional_price_formatted"
                            class="text-right"
                        >
                            <span
                                class="block text-[0.65rem] font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Precio promocional
                            </span>
                            <span class="text-lg font-semibold">
                                {{ item.promotional_price_formatted }}
                            </span>
                            <span
                                v-if="item.previous_price_formatted"
                                class="block text-xs text-muted-foreground line-through"
                            >
                                {{ item.previous_price_formatted }}
                            </span>
                        </p>
                    </div>

                    <div class="flex-1 space-y-3 p-4">
                        <div
                            v-if="item.applies_on.length > 0"
                            class="flex flex-wrap gap-1"
                        >
                            <Badge
                                v-for="day in item.applies_on"
                                :key="day"
                                variant="outline"
                            >
                                {{ day }}
                            </Badge>
                        </div>
                        <p
                            v-if="item.description"
                            class="text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ item.description }}
                        </p>
                        <p
                            v-if="item.conditions"
                            class="rounded-lg bg-muted/30 p-2 text-xs text-muted-foreground"
                        >
                            {{ item.conditions }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 border-t p-3">
                        <Button size="sm" variant="outline" @click="edit(item)">
                            <Pencil class="size-4" />
                            Editar
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            class="text-destructive hover:text-destructive"
                            @click="destroy(item)"
                        >
                            <Trash2 class="size-4" />
                            Eliminar
                        </Button>
                    </div>
                </article>
            </div>
        </section>

        <Dialog :open="dialogOpen" @update:open="handleDialogOpen">
            <DialogScrollContent class="sm:max-w-2xl">
                <form class="grid gap-5" @submit.prevent="submit" novalidate>
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editing ? 'Editar promoción' : 'Nueva promoción'
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                editing
                                    ? 'Modifica la vigencia o las condiciones de la promoción.'
                                    : 'Define la oferta, su vigencia y los días aplicables.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <RequiredFieldsHint />

                    <div class="grid gap-4">
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
                            <Label for="promotion-description">
                                Descripción
                            </Label>
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
                                <Label for="promotion-price">
                                    Precio promocional
                                </Label>
                                <Input
                                    id="promotion-price"
                                    v-model="form.promotional_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                />
                                <p
                                    v-if="form.errors.promotional_price"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.promotional_price }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="promotion-previous">
                                    Precio anterior
                                </Label>
                                <Input
                                    id="promotion-previous"
                                    v-model="form.previous_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                />
                                <p
                                    v-if="form.errors.previous_price"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.previous_price }}
                                </p>
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
                            <Label for="promotion-conditions">
                                Condiciones
                            </Label>
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
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing">
                            <Check class="size-4" />
                            {{
                                editing ? 'Guardar cambios' : 'Crear promoción'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </div>
</template>
