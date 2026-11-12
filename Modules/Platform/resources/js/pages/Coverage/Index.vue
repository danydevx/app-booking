<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    ChevronUp,
    MapPinned,
    Save,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

type CoverageMunicipality = {
    id: number;
    code: string;
    name: string;
    enabled: boolean;
};

type CoverageState = {
    id: number;
    code: string;
    name: string;
    enabled: boolean;
    all_municipalities: boolean;
    municipalities_count: number;
    enabled_municipalities_count: number;
    municipalities: CoverageMunicipality[];
};

type CoveragePayload = {
    id: number;
    enabled: boolean;
    all_municipalities: boolean;
    municipality_ids: number[];
};

const props = defineProps<{
    states: CoverageState[];
    urls: {
        update: string;
        dashboard: string;
    };
}>();

const states = ref<CoverageState[]>(
    props.states.map((state) => ({
        ...state,
        municipalities: state.municipalities.map((municipality) => ({
            ...municipality,
        })),
    })),
);

const search = ref('');
const expanded = ref<Record<number, boolean>>({});
const savedTotal = ref(enabledMunicipalities(props.states));

const form = useForm<{ states: CoveragePayload[] }>({ states: [] });

const filteredStates = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return states.value;
    }

    return states.value.filter(
        (state) =>
            state.name.toLowerCase().includes(term) ||
            state.code.includes(term),
    );
});

const enabledStates = computed(
    () => states.value.filter((state) => state.enabled).length,
);

const enabledMunicipalitiesCount = computed(() =>
    states.value.reduce((total, state) => total + stateCount(state), 0),
);

const totalMunicipalities = computed(() =>
    states.value.reduce(
        (total, state) => total + state.municipalities.length,
        0,
    ),
);

const errorMessages = computed(() =>
    Object.values(form.errors).filter(
        (message): message is string =>
            typeof message === 'string' && message !== '',
    ),
);

function enabledMunicipalities(source: CoverageState[]): number {
    return source.reduce((total, state) => total + stateCount(state), 0);
}

function stateCount(state: CoverageState): number {
    if (!state.enabled) {
        return 0;
    }

    return state.all_municipalities
        ? state.municipalities.length
        : state.municipalities.filter((municipality) => municipality.enabled)
              .length;
}

function toggleState(state: CoverageState): void {
    state.enabled = !state.enabled;

    if (
        state.enabled &&
        !state.all_municipalities &&
        !state.municipalities.some((municipality) => municipality.enabled)
    ) {
        setAllMunicipalities(state);
    }
}

function setAllMunicipalities(state: CoverageState): void {
    state.all_municipalities = true;
    state.municipalities.forEach((municipality) => {
        municipality.enabled = true;
    });
}

function setPartialCoverage(state: CoverageState): void {
    state.all_municipalities = false;
}

function selectAll(state: CoverageState): void {
    state.municipalities.forEach((municipality) => {
        municipality.enabled = true;
    });
}

function clearAll(state: CoverageState): void {
    state.municipalities.forEach((municipality) => {
        municipality.enabled = false;
    });
}

function toggleExpanded(state: CoverageState): void {
    expanded.value = {
        ...expanded.value,
        [state.id]: !expanded.value[state.id],
    };
}

function confirmReduction(): boolean {
    const total = enabledMunicipalitiesCount.value;

    if (total >= savedTotal.value) {
        return true;
    }

    return window.confirm(
        'Al reducir la cobertura, los espacios publicados en las zonas afectadas dejarán de mostrarse en el catálogo y no recibirán nuevas solicitudes. ¿Deseas continuar?',
    );
}

function save(): void {
    if (!confirmReduction()) {
        return;
    }

    form.transform(() => ({
        states: states.value.map((state): CoveragePayload => ({
            id: state.id,
            enabled: state.enabled,
            all_municipalities: state.all_municipalities,
            municipality_ids: state.all_municipalities
                ? []
                : state.municipalities
                      .filter((municipality) => municipality.enabled)
                      .map((municipality) => municipality.id),
        })),
    })).patch(props.urls.update, {
        preserveScroll: true,
        onSuccess: () => {
            savedTotal.value = enabledMunicipalitiesCount.value;
        },
    });
}
</script>

<template>
    <Head title="Cobertura geográfica" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div
                    class="flex items-center gap-2 text-sm font-medium text-primary"
                >
                    <MapPinned class="size-4" />
                    Configuración
                </div>
                <h1 class="mt-1 text-2xl font-semibold">
                    Cobertura geográfica
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Define en qué estados y municipios puede operar Booking. Los
                    espacios fuera de cobertura se conservan en la
                    administración, pero dejan de mostrarse en el catálogo
                    público y no reciben nuevas solicitudes.
                </p>
            </div>

            <Button :disabled="form.processing" @click="save">
                <Save class="size-4" />
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </Button>
        </div>

        <div
            v-if="errorMessages.length > 0"
            class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
            role="alert"
        >
            <p v-for="message in errorMessages" :key="message">
                {{ message }}
            </p>
        </div>

        <div
            class="grid gap-3 rounded-xl border bg-muted/20 p-4 sm:grid-cols-3"
        >
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Estados habilitados
                </p>
                <p class="mt-1 text-2xl font-semibold">{{ enabledStates }}</p>
            </div>
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Municipios habilitados
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ enabledMunicipalitiesCount }}
                    <span class="text-sm font-normal text-muted-foreground">
                        / {{ totalMunicipalities }}
                    </span>
                </p>
            </div>
            <div class="flex items-end">
                <p
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Check class="size-3.5" />
                    Solo el Superadmin puede modificar esta configuración.
                </p>
            </div>
        </div>

        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                class="pl-9"
                placeholder="Buscar estado por nombre o clave INEGI"
                aria-label="Buscar estado"
            />
        </div>

        <div class="space-y-3">
            <article
                v-for="state in filteredStates"
                :key="state.id"
                class="rounded-xl border bg-card p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex min-w-0 items-center gap-3">
                        <Checkbox
                            :model-value="state.enabled"
                            :aria-label="`Habilitar ${state.name}`"
                            @update:model-value="toggleState(state)"
                        />
                        <span class="min-w-0">
                            <span class="block font-semibold">
                                {{ state.name }}
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                Clave INEGI {{ state.code }}
                            </span>
                        </span>
                    </label>

                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            :variant="state.enabled ? 'secondary' : 'outline'"
                        >
                            {{
                                !state.enabled
                                    ? 'Deshabilitado'
                                    : state.all_municipalities
                                      ? `${state.municipalities.length} / ${state.municipalities.length} municipios`
                                      : `${stateCount(state)} / ${state.municipalities.length} municipios`
                            }}
                        </Badge>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="toggleExpanded(state)"
                        >
                            Gestionar municipios
                            <ChevronUp
                                v-if="expanded[state.id]"
                                class="size-4"
                            />
                            <ChevronDown v-else class="size-4" />
                        </Button>
                    </div>
                </div>

                <div v-if="state.enabled" class="mt-3 flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        :variant="
                            state.all_municipalities ? 'default' : 'outline'
                        "
                        @click="setAllMunicipalities(state)"
                    >
                        Todos los municipios
                    </Button>
                    <Button
                        size="sm"
                        :variant="
                            !state.all_municipalities ? 'default' : 'outline'
                        "
                        @click="setPartialCoverage(state)"
                    >
                        Selección parcial
                    </Button>
                </div>

                <div v-if="expanded[state.id]" class="mt-3 border-t pt-3">
                    <p
                        v-if="!state.enabled"
                        class="text-sm text-muted-foreground"
                    >
                        Activa el estado para habilitar sus municipios.
                    </p>

                    <template v-else>
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <p class="text-xs text-muted-foreground">
                                {{ stateCount(state) }} de
                                {{ state.municipalities.length }} municipios
                                habilitados
                            </p>
                            <div
                                v-if="!state.all_municipalities"
                                class="flex gap-2"
                            >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="selectAll(state)"
                                >
                                    Seleccionar todos
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="clearAll(state)"
                                >
                                    Quitar todos
                                </Button>
                            </div>
                        </div>

                        <div
                            class="mt-3 grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <label
                                v-for="municipality in state.municipalities"
                                :key="municipality.id"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                                :class="{
                                    'cursor-not-allowed opacity-70':
                                        state.all_municipalities,
                                }"
                            >
                                <Checkbox
                                    v-model="municipality.enabled"
                                    :disabled="state.all_municipalities"
                                />
                                <span class="min-w-0">
                                    {{ municipality.name }}
                                </span>
                            </label>
                        </div>
                    </template>
                </div>
            </article>
        </div>

        <p
            v-if="filteredStates.length === 0"
            class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
        >
            No hay estados que coincidan con la búsqueda.
        </p>

        <div class="flex justify-end">
            <Button :disabled="form.processing" @click="save">
                <Save class="size-4" />
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </Button>
        </div>
    </div>
</template>
