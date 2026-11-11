<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    CreditCard,
    Layers,
    Pencil,
    Plus,
    Sparkles,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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

type PlanRow = {
    slug: string;
    name: string;
    description: string;
    stripe_price_id: string | null;
    interval: string;
    active: boolean;
    is_free: boolean;
    price_configured: boolean;
    limits: Record<string, number>;
    features: Record<string, boolean>;
    update_url: string;
};

const props = defineProps<{
    plans: PlanRow[];
    limitLabels: Record<string, string>;
    featureLabels: Record<string, string>;
    urls: {
        index: string;
        store: string;
        dashboard: string;
    };
}>();

const editing = ref<PlanRow | null>(null);
const dialogOpen = ref(false);

function blankLimits(): Record<string, number> {
    return Object.fromEntries(
        Object.keys(props.limitLabels).map((key) => [key, 0]),
    );
}

function blankFeatures(): Record<string, boolean> {
    return Object.fromEntries(
        Object.keys(props.featureLabels).map((key) => [key, false]),
    );
}

const form = useForm({
    slug: '',
    name: '',
    description: '',
    stripe_price_id: '',
    interval: 'month',
    active: true,
    limits: blankLimits(),
    features: blankFeatures(),
});

const activeCount = computed(
    () => props.plans.filter((plan) => plan.active).length,
);

const priceConfiguredCount = computed(
    () => props.plans.filter((plan) => plan.price_configured).length,
);

const enabledFeatureCount = computed(() =>
    props.plans.reduce(
        (total, plan) =>
            total + Object.values(plan.features).filter(Boolean).length,
        0,
    ),
);

function openCreate(): void {
    reset();
    dialogOpen.value = true;
}

function edit(plan: PlanRow): void {
    editing.value = plan;
    form.slug = plan.slug;
    form.name = plan.name;
    form.description = plan.description;
    form.stripe_price_id = plan.stripe_price_id ?? '';
    form.interval = plan.interval;
    form.active = plan.is_free ? true : plan.active;
    form.limits = { ...blankLimits(), ...plan.limits };
    form.features = { ...blankFeatures(), ...plan.features };
    form.clearErrors();
    dialogOpen.value = true;
}

function reset(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.slug = '';
    form.name = '';
    form.description = '';
    form.stripe_price_id = '';
    form.interval = 'month';
    form.active = true;
    form.limits = blankLimits();
    form.features = blankFeatures();
}

function handleDialogOpen(open: boolean): void {
    dialogOpen.value = open;

    if (!open) {
        reset();
    }
}

function fieldError(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

function submit(): void {
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

function intervalLabel(interval: string): string {
    return interval === 'year' ? 'Anual' : 'Mensual';
}
</script>

<template>
    <Head title="Planes · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">Planes y features</h1>
                    <Badge variant="outline">Plataforma</Badge>
                </div>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Administra los límites y funcionalidades de cada plan. Los
                    cambios aplican a todos los equipos en su siguiente
                    petición.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.dashboard">Resumen</Link>
                </Button>
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" />
                    Nuevo plan
                </Button>
            </div>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Layers class="size-4" />
                    Planes
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ plans.length }}
                </p>
                <p class="text-xs text-muted-foreground">Configurados</p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Check class="size-4" />
                    Activos
                </div>
                <p class="mt-2 text-2xl font-semibold">{{ activeCount }}</p>
                <p class="text-xs text-muted-foreground">
                    Visibles para contratación
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <CreditCard class="size-4" />
                    Con precio Stripe
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ priceConfiguredCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Listos para checkout
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <Sparkles class="size-4" />
                    Features activos
                </div>
                <p class="mt-2 text-2xl font-semibold">
                    {{ enabledFeatureCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Suma entre todos los planes
                </p>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Planes configurados</h2>
                    <p class="text-sm text-muted-foreground">
                        Los planes inactivos no se pueden contratar.
                    </p>
                </div>
                <Badge variant="secondary">
                    {{ plans.length }}
                    {{ plans.length === 1 ? 'plan' : 'planes' }}
                </Badge>
            </div>

            <div
                v-if="plans.length === 0"
                class="rounded-xl border border-dashed p-10 text-center"
            >
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <Layers class="size-6" />
                </div>
                <p class="mt-3 font-medium">Sin planes configurados</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Crea el primer plan para definir límites y features.
                </p>
                <Button
                    type="button"
                    class="mt-4"
                    variant="outline"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    Crear primer plan
                </Button>
            </div>

            <div
                v-else
                class="grid items-start gap-4 lg:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="plan in plans"
                    :key="plan.slug"
                    class="flex flex-col overflow-hidden rounded-xl border bg-card"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Layers class="size-4" />
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">
                                        {{ plan.name }}
                                    </h3>
                                    <Badge
                                        :variant="
                                            plan.active
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            plan.active ? 'Activo' : 'Inactivo'
                                        }}
                                    </Badge>
                                    <Badge
                                        v-if="plan.is_free"
                                        variant="outline"
                                    >
                                        Gratis
                                    </Badge>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ plan.slug }} ·
                                    {{ intervalLabel(plan.interval) }}
                                </p>
                            </div>
                        </div>
                        <Button size="sm" variant="outline" @click="edit(plan)">
                            <Pencil class="size-4" />
                            Editar
                        </Button>
                    </div>

                    <div class="flex-1 space-y-3 p-4">
                        <p
                            v-if="plan.description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ plan.description }}
                        </p>

                        <div class="flex items-center gap-2">
                            <CreditCard class="size-4 text-muted-foreground" />
                            <Badge
                                :variant="
                                    plan.price_configured
                                        ? 'default'
                                        : 'outline'
                                "
                            >
                                {{
                                    plan.price_configured
                                        ? 'Stripe configurado'
                                        : 'Sin precio de Stripe'
                                }}
                            </Badge>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Límites
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <Badge
                                    v-for="(label, key) in limitLabels"
                                    :key="key"
                                    variant="outline"
                                >
                                    {{ label }}: {{ plan.limits[key] ?? 0 }}
                                </Badge>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Features
                            </p>
                            <div class="mt-2 grid gap-1 sm:grid-cols-2">
                                <span
                                    v-for="(label, key) in featureLabels"
                                    :key="key"
                                    class="flex items-center gap-1.5 text-xs"
                                    :class="
                                        plan.features[key]
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    <Check
                                        v-if="plan.features[key]"
                                        class="size-3.5 text-primary"
                                    />
                                    <X v-else class="size-3.5" />
                                    {{ label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <Dialog :open="dialogOpen" @update:open="handleDialogOpen">
            <DialogScrollContent class="sm:max-w-3xl">
                <form class="grid gap-5" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editing ? 'Editar plan' : 'Nuevo plan' }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                editing
                                    ? 'Los cambios aplican a todos los equipos en su siguiente petición.'
                                    : 'Define los límites y features del nuevo plan.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <RequiredFieldsHint />

                    <section class="grid gap-4">
                        <div class="flex items-center gap-2">
                            <Layers class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">Identidad</h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="plan-slug">Slug</Label>
                                <Input
                                    id="plan-slug"
                                    v-model="form.slug"
                                    :disabled="editing !== null"
                                    maxlength="40"
                                    required
                                    placeholder="premium"
                                />
                                <p class="text-xs text-muted-foreground">
                                    No se puede cambiar después de crear el
                                    plan.
                                </p>
                                <p
                                    v-if="form.errors.slug"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.slug }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="plan-name">Nombre</Label>
                                <Input
                                    id="plan-name"
                                    v-model="form.name"
                                    maxlength="80"
                                    required
                                    placeholder="Premium"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="plan-description">Descripción</Label>
                            <textarea
                                id="plan-description"
                                v-model="form.description"
                                rows="3"
                                maxlength="500"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Para anfitriones con mayor operación."
                            />
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </section>

                    <section class="grid gap-4 border-t pt-5">
                        <div class="flex items-center gap-2">
                            <CreditCard class="size-4 text-primary" />
                            <h3 class="text-sm font-semibold">
                                Cobro y disponibilidad
                            </h3>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="plan-stripe-price">
                                    Precio de Stripe
                                </Label>
                                <Input
                                    id="plan-stripe-price"
                                    v-model="form.stripe_price_id"
                                    maxlength="255"
                                    placeholder="price_..."
                                />
                                <p class="text-xs text-muted-foreground">
                                    ID de un precio existente en Stripe. Si se
                                    deja vacío, el plan no podrá contratarse.
                                </p>
                                <p
                                    v-if="form.errors.stripe_price_id"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.stripe_price_id }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="plan-interval">Intervalo</Label>
                                <select
                                    id="plan-interval"
                                    v-model="form.interval"
                                    required
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="month">Mensual</option>
                                    <option value="year">Anual</option>
                                </select>
                                <p
                                    v-if="form.errors.interval"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.interval }}
                                </p>
                            </div>
                        </div>
                        <label
                            class="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm"
                        >
                            <Checkbox
                                v-model:checked="form.active"
                                :disabled="editing?.is_free === true"
                            />
                            {{
                                editing?.is_free
                                    ? 'El plan Gratis siempre está activo'
                                    : 'Plan activo y disponible para contratación'
                            }}
                        </label>
                        <p
                            v-if="form.errors.active"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.active }}
                        </p>
                        <p
                            v-if="editing && !form.active && !editing.is_free"
                            class="rounded-lg bg-amber-50 p-2 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                        >
                            Los equipos actuales conservan su plan. Desactivarlo
                            solo impide nuevas contrataciones.
                        </p>
                    </section>

                    <section class="grid gap-3 border-t pt-5">
                        <div>
                            <div class="flex items-center gap-2">
                                <Layers class="size-4 text-primary" />
                                <h3 class="text-sm font-semibold">Límites</h3>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                0 significa que la función no está disponible.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div
                                v-for="(label, key) in limitLabels"
                                :key="key"
                                class="grid gap-2"
                            >
                                <Label :for="`limit-${key}`">
                                    {{ label }}
                                </Label>
                                <Input
                                    :id="`limit-${key}`"
                                    v-model.number="form.limits[key]"
                                    type="number"
                                    min="0"
                                    max="1000000"
                                    required
                                />
                                <p
                                    v-if="fieldError(`limits.${key}`)"
                                    class="text-xs text-destructive"
                                >
                                    {{ fieldError(`limits.${key}`) }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.limits"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.limits }}
                        </p>
                    </section>

                    <section class="grid gap-3 border-t pt-5">
                        <div>
                            <div class="flex items-center gap-2">
                                <Sparkles class="size-4 text-primary" />
                                <h3 class="text-sm font-semibold">Features</h3>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Funciones que se habilitan con este plan.
                            </p>
                        </div>
                        <div
                            class="grid gap-3 rounded-lg border bg-muted/20 p-3 sm:grid-cols-2"
                        >
                            <div
                                v-for="(label, key) in featureLabels"
                                :key="key"
                                class="grid gap-1"
                            >
                                <label class="flex items-center gap-3 text-sm">
                                    <Checkbox
                                        v-model:checked="form.features[key]"
                                    />
                                    {{ label }}
                                </label>
                                <p
                                    v-if="fieldError(`features.${key}`)"
                                    class="text-xs text-destructive"
                                >
                                    {{ fieldError(`features.${key}`) }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.features"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.features }}
                        </p>
                    </section>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing">
                            <Check class="size-4" />
                            {{ editing ? 'Guardar plan' : 'Crear plan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </div>
</template>
