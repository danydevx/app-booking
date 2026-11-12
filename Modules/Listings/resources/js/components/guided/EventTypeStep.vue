<script setup lang="ts">
import {
    Baby,
    Briefcase,
    Cake,
    Calendar,
    Church,
    Droplets,
    Gem,
    GraduationCap,
    Heart,
    Minus,
    PartyPopper,
    Plus,
    Sparkles,
    Star,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import GuidedSearchStep from './GuidedSearchStep.vue';

type EventTypeOption = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    icon: string | null;
};

const props = defineProps<{
    eventTypes: EventTypeOption[];
    eventSlug: string | null;
    guests: number | null;
    summary: string[];
}>();

const emit = defineEmits<{
    'update:eventSlug': [value: string | null];
    'update:guests': [value: number | null];
}>();

const iconMap: Record<string, Component> = {
    baby: Baby,
    briefcase: Briefcase,
    cake: Cake,
    calendar: Calendar,
    church: Church,
    droplets: Droplets,
    gem: Gem,
    'graduation-cap': GraduationCap,
    heart: Heart,
    'party-popper': PartyPopper,
    sparkles: Sparkles,
    star: Star,
    users: Users,
};

const guestInput = ref(props.guests !== null ? String(props.guests) : '');
const guestError = ref<string | null>(null);

const selectedEvent = computed(() =>
    props.eventTypes.find((eventType) => eventType.slug === props.eventSlug),
);

function iconFor(icon: string | null): Component {
    return iconMap[icon ?? ''] ?? Sparkles;
}

function selectEvent(slug: string): void {
    emit('update:eventSlug', props.eventSlug === slug ? null : slug);
}

function commitGuests(): void {
    const value = String(guestInput.value ?? '').trim();

    if (value === '') {
        guestError.value = null;
        emit('update:guests', null);

        return;
    }

    const guests = Number(value);

    if (!Number.isInteger(guests) || guests < 1) {
        guestError.value = 'Ingresa un número positivo de personas.';

        return;
    }

    if (guests > 100000) {
        guestError.value = 'El máximo permitido es 100,000 personas.';

        return;
    }

    guestError.value = null;
    emit('update:guests', guests);
}

function adjustGuests(delta: number): void {
    const current = props.guests ?? 0;
    const next = Math.max(1, Math.min(100000, current + delta));

    guestInput.value = String(next);
    guestError.value = null;
    emit('update:guests', next);
}

function chooseGuests(value: number): void {
    guestInput.value = String(value);
    guestError.value = null;
    emit('update:guests', value);
}

watch(
    () => props.guests,
    (value) => {
        if (value !== null && guestInput.value !== String(value)) {
            guestInput.value = String(value);
        }

        if (value === null) {
            guestInput.value = '';
        }
    },
);
</script>

<template>
    <GuidedSearchStep
        title="¿Qué vamos a celebrar?"
        subtitle="Queremos encontrar espacios adecuados para tu ocasión. Puedes elegir un tipo de evento o continuar sin definirlo."
    >
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="eventType in eventTypes"
                :key="eventType.slug"
                type="button"
                class="flex items-start gap-3 rounded-[1rem] border p-4 text-left transition-colors"
                :class="
                    eventSlug === eventType.slug
                        ? 'border-[color:var(--home-primary)] bg-[color:var(--home-soft)]'
                        : 'border-[color:var(--home-border)] bg-white hover:border-[color:var(--home-primary)]/40'
                "
                :aria-pressed="eventSlug === eventType.slug"
                @click="selectEvent(eventType.slug)"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-[0.75rem]"
                    :class="
                        eventSlug === eventType.slug
                            ? 'bg-[color:var(--home-primary)] text-white'
                            : 'bg-[color:var(--home-bg-alt)] text-[color:var(--home-primary-deep)]'
                    "
                >
                    <component
                        :is="iconFor(eventType.icon)"
                        class="size-5"
                        aria-hidden="true"
                    />
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-bold">
                        {{ eventType.name }}
                    </span>
                    <span
                        v-if="eventType.description"
                        class="mt-1 block text-xs leading-relaxed text-[color:var(--home-secondary)]"
                    >
                        {{ eventType.description }}
                    </span>
                </span>
            </button>
        </div>

        <p
            v-if="selectedEvent"
            class="mt-3 flex flex-wrap items-center gap-2 text-xs text-[color:var(--home-muted)]"
        >
            Tipo seleccionado: {{ selectedEvent.name }}
            <button
                type="button"
                class="font-semibold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                @click="emit('update:eventSlug', null)"
            >
                Quitar selección
            </button>
        </p>

        <div class="mt-8 max-w-xl">
            <label class="home-field-label" for="guided-guests">
                ¿Cuántas personas asistirán?
            </label>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="home-btn home-btn--outline size-10 shrink-0 p-0"
                    aria-label="Quitar una persona"
                    :disabled="guests === null || guests <= 1"
                    @click="adjustGuests(-1)"
                >
                    <Minus class="size-4" aria-hidden="true" />
                </button>
                <input
                    id="guided-guests"
                    v-model="guestInput"
                    class="home-control text-center text-lg font-bold"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="100000"
                    placeholder="Ej. 80"
                    @input="commitGuests"
                    @blur="commitGuests"
                />
                <button
                    type="button"
                    class="home-btn home-btn--outline size-10 shrink-0 p-0"
                    aria-label="Agregar una persona"
                    @click="adjustGuests(1)"
                >
                    <Plus class="size-4" aria-hidden="true" />
                </button>
            </div>
            <p
                v-if="guestError"
                class="mt-2 text-sm font-medium text-[color:var(--home-primary-deep)]"
                role="alert"
            >
                {{ guestError }}
            </p>
            <p v-else class="mt-2 text-xs text-[color:var(--home-muted)]">
                Si aún no sabes la cantidad exacta, puedes continuar sin este
                dato.
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    v-for="option in [45, 80, 120, 250]"
                    :key="option"
                    type="button"
                    class="home-chip transition-colors hover:border-[color:var(--home-primary)]/40"
                    @click="chooseGuests(option)"
                >
                    {{ option }} personas
                </button>
            </div>
        </div>

        <div
            v-if="summary.length > 0"
            class="mt-8 rounded-[1rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] p-4"
        >
            <p
                class="text-xs font-bold tracking-[0.1em] text-[color:var(--home-muted)] uppercase"
            >
                Tu búsqueda hasta ahora
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                <span
                    v-for="item in summary"
                    :key="item"
                    class="home-chip bg-white"
                >
                    {{ item }}
                </span>
            </div>
        </div>
    </GuidedSearchStep>
</template>
