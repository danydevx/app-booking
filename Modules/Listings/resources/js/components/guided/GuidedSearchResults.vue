<script setup lang="ts">
import { Loader2, RotateCcw, SearchX, SlidersHorizontal } from '@lucide/vue';
import GuidedVenueCard from './GuidedVenueCard.vue';
import type { GuidedResult } from '../../composables/useGuidedSearch';

type CriteriaItem = {
    label: string;
    value: string;
    step: number;
};

defineProps<{
    listings: GuidedResult[];
    total: number;
    loading: boolean;
    loadingMore: boolean;
    searched: boolean;
    error: string | null;
    currentPage: number;
    lastPage: number;
    criteria: CriteriaItem[];
    dateNote: string | null;
}>();

const emit = defineEmits<{
    modify: [step: number];
    restart: [];
    retry: [];
    loadMore: [];
    contact: [listing: GuidedResult];
}>();
</script>

<template>
    <section aria-live="polite">
        <div
            v-if="loading"
            class="mx-auto flex max-w-2xl flex-col items-center rounded-[1.25rem] border border-[color:var(--home-border)] bg-white px-6 py-16 text-center"
            role="status"
        >
            <Loader2
                class="size-9 animate-spin text-[color:var(--home-primary)] motion-reduce:animate-none"
                aria-hidden="true"
            />
            <h2 class="mt-5 text-xl font-extrabold">
                Buscando espacios para tu evento...
            </h2>
            <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                Estamos consultando espacios que cumplen tus criterios.
            </p>
        </div>

        <div
            v-else-if="error"
            class="mx-auto max-w-2xl rounded-[1.25rem] border border-[color:var(--home-border)] bg-white px-6 py-12 text-center"
            role="alert"
        >
            <h2 class="text-xl font-extrabold">
                No pudimos completar la búsqueda
            </h2>
            <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                {{ error }}
            </p>
            <button
                type="button"
                class="home-btn home-btn--primary mt-5"
                @click="emit('retry')"
            >
                Intentar de nuevo
            </button>
        </div>

        <div
            v-else-if="searched && total === 0"
            class="mx-auto max-w-2xl rounded-[1.25rem] border border-[color:var(--home-border)] bg-white px-6 py-12 text-center"
        >
            <SearchX
                class="mx-auto size-10 text-[color:var(--home-muted)]"
                aria-hidden="true"
            />
            <h2 class="mt-4 text-2xl font-extrabold">
                No encontramos espacios con esa combinación
            </h2>
            <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                Prueba ampliando la búsqueda. No cambiaremos tus filtros sin
                avisarte.
            </p>

            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <button
                    type="button"
                    class="home-btn home-btn--outline home-btn--sm"
                    @click="emit('modify', 1)"
                >
                    Cambiar fechas
                </button>
                <button
                    type="button"
                    class="home-btn home-btn--outline home-btn--sm"
                    @click="emit('modify', 2)"
                >
                    Ampliar radio o zona
                </button>
                <button
                    type="button"
                    class="home-btn home-btn--outline home-btn--sm"
                    @click="emit('modify', 3)"
                >
                    Quitar características
                </button>
                <button
                    type="button"
                    class="home-btn home-btn--outline home-btn--sm"
                    @click="emit('modify', 4)"
                >
                    Cambiar invitados
                </button>
            </div>
            <button
                type="button"
                class="home-btn home-btn--primary mt-4"
                @click="emit('retry')"
            >
                Volver a buscar
            </button>
            <button
                type="button"
                class="mt-3 block w-full text-sm font-semibold text-[color:var(--home-primary-deep)] underline-offset-2 hover:underline"
                @click="emit('restart')"
            >
                Reiniciar búsqueda
            </button>
        </div>

        <div v-else>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="home-eyebrow">Resultados</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">
                        Encontramos {{ total }}
                        {{
                            total === 1 ? 'espacio para ti' : 'espacios para ti'
                        }}
                    </h2>
                    <p class="mt-2 text-sm text-[color:var(--home-secondary)]">
                        Ordenados por los espacios que mejor coinciden con tu
                        búsqueda.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="home-btn home-btn--outline home-btn--sm"
                        @click="emit('modify', 4)"
                    >
                        <SlidersHorizontal class="size-4" aria-hidden="true" />
                        Modificar búsqueda
                    </button>
                    <button
                        type="button"
                        class="home-btn home-btn--outline home-btn--sm"
                        @click="emit('restart')"
                    >
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Reiniciar
                    </button>
                </div>
            </div>

            <div
                v-if="criteria.length > 0"
                class="mt-5 flex flex-wrap items-center gap-2"
            >
                <span
                    v-for="item in criteria"
                    :key="`${item.label}-${item.value}`"
                    class="home-chip bg-white"
                >
                    {{ item.label }}: {{ item.value }}
                </span>
            </div>
            <p
                v-else
                class="mt-5 rounded-[0.875rem] bg-[color:var(--home-bg-alt)] p-3 text-sm text-[color:var(--home-secondary)]"
            >
                No aplicaste filtros adicionales. Mostramos espacios dentro de
                la cobertura habilitada.
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="text-xs font-semibold text-[color:var(--home-primary-deep)] underline-offset-2 hover:underline"
                    @click="emit('modify', 1)"
                >
                    Cambiar fecha
                </button>
                <button
                    type="button"
                    class="text-xs font-semibold text-[color:var(--home-primary-deep)] underline-offset-2 hover:underline"
                    @click="emit('modify', 2)"
                >
                    Cambiar ubicación
                </button>
                <button
                    type="button"
                    class="text-xs font-semibold text-[color:var(--home-primary-deep)] underline-offset-2 hover:underline"
                    @click="emit('modify', 3)"
                >
                    Cambiar características
                </button>
                <button
                    type="button"
                    class="text-xs font-semibold text-[color:var(--home-primary-deep)] underline-offset-2 hover:underline"
                    @click="emit('modify', 4)"
                >
                    Cambiar evento o invitados
                </button>
            </div>

            <p
                v-if="dateNote"
                class="mt-3 rounded-[0.875rem] border border-[color:var(--home-border)] bg-white p-3 text-xs text-[color:var(--home-muted)]"
            >
                {{ dateNote }}
            </p>

            <div class="mt-6 grid gap-4">
                <GuidedVenueCard
                    v-for="listing in listings"
                    :key="listing.id"
                    :listing="listing"
                    @contact="emit('contact', $event)"
                />
            </div>

            <div class="mt-6 flex justify-center">
                <button
                    v-if="currentPage < lastPage"
                    type="button"
                    class="home-btn home-btn--outline"
                    :disabled="loadingMore"
                    @click="emit('loadMore')"
                >
                    <Loader2
                        v-if="loadingMore"
                        class="size-4 animate-spin motion-reduce:animate-none"
                        aria-hidden="true"
                    />
                    {{
                        loadingMore
                            ? 'Cargando más espacios...'
                            : 'Cargar más espacios'
                    }}
                </button>
            </div>
        </div>
    </section>
</template>
