<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type HomeVenue = {
    id: number;
    name: string;
    city: string | null;
    state: string | null;
    capacity: number | null;
    services: string[];
    cover_url: string | null;
    price_from_formatted?: string | null;
    url: string;
};

const props = withDefaults(
    defineProps<{
        listing: HomeVenue;
        selected?: boolean;
        selectable?: boolean;
    }>(),
    {
        selected: false,
        selectable: false,
    },
);

const emit = defineEmits<{
    (event: 'select', id: number): void;
}>();

const coverFailed = ref(false);

watch(
    () => props.listing.cover_url,
    () => {
        coverFailed.value = false;
    },
);

const hasCover = computed(
    () => Boolean(props.listing.cover_url) && !coverFailed.value,
);

const place = computed(() =>
    [props.listing.city, props.listing.state].filter(Boolean).join(', '),
);

const services = computed(() => props.listing.services.slice(0, 3));

const initial = computed(() => props.listing.name.trim().charAt(0) || 'A');
</script>

<template>
    <article
        :id="`venue-${listing.id}`"
        class="home-card group h-full"
        :class="{
            'ring-2 ring-[color:var(--home-primary)] ring-offset-2 ring-offset-[color:var(--home-bg)]':
                selected,
        }"
        :data-selected="selected ? 'true' : 'false'"
    >
        <component
            :is="selectable ? 'button' : Link"
            :href="selectable ? undefined : listing.url"
            :type="selectable ? 'button' : undefined"
            class="flex w-full flex-1 flex-col text-left"
            :aria-label="
                selectable
                    ? `Seleccionar ${listing.name} en el mapa`
                    : `Ver ${listing.name}`
            "
            @click="selectable ? emit('select', listing.id) : undefined"
        >
            <div class="home-media aspect-[3/2]">
                <img
                    v-if="hasCover"
                    :src="listing.cover_url ?? ''"
                    :alt="`Fotografía de ${listing.name}`"
                    loading="lazy"
                    decoding="async"
                    @error="coverFailed = true"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center bg-[color:var(--home-soft)]"
                >
                    <span
                        class="text-4xl font-extrabold text-[color:var(--home-primary)]/40"
                        aria-hidden="true"
                    >
                        {{ initial }}
                    </span>
                </div>
            </div>

            <div class="flex flex-1 flex-col gap-2.5 p-5">
                <p
                    v-if="place"
                    class="text-sm font-medium text-[color:var(--home-secondary)]"
                >
                    {{ place }}
                </p>
                <h3
                    class="text-xl leading-snug font-bold text-[color:var(--home-dark)] sm:text-[1.375rem]"
                >
                    {{ listing.name }}
                </h3>

                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.9375rem] text-[color:var(--home-secondary)]"
                >
                    <span v-if="listing.capacity" class="font-medium">
                        Hasta
                        <strong class="font-bold text-[color:var(--home-dark)]">
                            {{ listing.capacity }}
                        </strong>
                        invitados
                    </span>
                    <span
                        v-if="listing.price_from_formatted"
                        class="font-medium"
                    >
                        Desde
                        <strong
                            class="font-bold text-[color:var(--home-primary-deep)]"
                        >
                            {{ listing.price_from_formatted }}
                        </strong>
                    </span>
                </div>

                <div
                    v-if="services.length > 0"
                    class="mt-1 flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="service in services"
                        :key="service"
                        class="home-chip"
                    >
                        {{ service }}
                    </span>
                </div>
            </div>
        </component>

        <div
            v-if="selectable"
            class="flex items-center justify-between gap-3 border-t border-[color:var(--home-border)] px-4 py-2.5"
        >
            <span class="text-xs text-[color:var(--home-muted)]">
                Ubícala en el mapa
            </span>
            <Link
                :href="listing.url"
                class="text-sm font-bold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
            >
                Ver detalle
            </Link>
        </div>
    </article>
</template>
