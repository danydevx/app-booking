<script setup lang="ts">
import '@/homeSite';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import HomeSearch from '@/components/home/HomeSearch.vue';
import HomeVenueCard from '@/components/home/HomeVenueCard.vue';

type HomeListing = {
    id: number;
    name: string;
    city: string | null;
    state: string | null;
    capacity: number | null;
    services: string[];
    cover_url: string | null;
    price_from_formatted?: string | null;
    distance_km: number | null;
    url: string;
};

type HomeCity = {
    state: string;
    city: string;
    count: number;
};

const props = defineProps<{
    listings: HomeListing[];
    cities: HomeCity[];
    totalListings: number;
    locations: Record<string, { label: string; municipalities: string[] }>;
    services: Array<{ value: string; label: string }>;
    urls: {
        index: string;
    };
}>();

const page = usePage();

const appName = computed(
    () =>
        (page.props.branding as { app_name?: string } | undefined)?.app_name ??
        'AApp Booking',
);

const unsplash = (id: string, width = 1200): string =>
    `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${width}&q=80`;

const heroImage = unsplash('1561501900-3701fa6a0864', 2400);
const ownerImage = unsplash('1540541338287-41700207dee6', 1400);

const cityUrl = (city: HomeCity): string =>
    `${props.urls.index}?state=${encodeURIComponent(city.state)}&city=${encodeURIComponent(city.city)}`;

const spaceTypeDefinitions = [
    {
        value: 'garden',
        title: 'Jardines',
        copy: 'Mesas al aire libre',
        image: unsplash('1519225421980-715cb0215aed', 900),
    },
    {
        value: 'pool',
        title: 'Con alberca',
        copy: 'Para días de calor',
        image: unsplash('1572331165267-854da2b10ccc', 900),
    },
    {
        value: 'covered_terrace',
        title: 'Terrazas techadas',
        copy: 'Lluvia o sol',
        image: unsplash('1519167758481-83f550bb49b3', 900),
    },
    {
        value: 'green_areas',
        title: 'Áreas verdes',
        copy: 'Verdes y sombra',
        image: unsplash('1558904541-efa843a96f01', 900),
    },
];

const spaceTypes = computed(() =>
    spaceTypeDefinitions
        .filter((type) =>
            props.services.some((service) => service.value === type.value),
        )
        .map((type) => ({
            ...type,
            href: `${props.urls.index}?service=${encodeURIComponent(type.value)}`,
        })),
);

const steps = [
    {
        title: 'Explora espacios',
        copy: 'Filtra por zona, invitados y características del lugar.',
    },
    {
        title: 'Revisa la información',
        copy: 'Fotos, capacidad, precios y disponibilidad de cada espacio.',
    },
    {
        title: 'Contacta al propietario',
        copy: 'Envía tu solicitud con fecha e invitados, sin crear cuenta.',
    },
];
</script>

<template>
    <Head :title="`${appName} · Espacios para celebrar`" />

    <section class="relative">
        <img
            :src="heroImage"
            alt="Terraza preparada para un evento al atardecer"
            class="absolute inset-0 h-full w-full object-cover"
            fetchpriority="high"
        />
        <div
            class="home-hero-overlay absolute inset-0"
            aria-hidden="true"
        ></div>

        <div
            class="home-container relative flex min-h-[32rem] flex-col justify-end pt-20 pb-36 sm:min-h-[35rem] sm:pb-40 lg:min-h-[40rem] lg:pb-44"
        >
            <p
                class="text-[0.8125rem] font-bold tracking-[0.18em] text-white/85 uppercase sm:text-sm"
            >
                Terrazas · Jardines · Salones
            </p>
            <h1
                class="mt-4 max-w-4xl text-[2.75rem] leading-[1.04] font-extrabold text-white sm:text-5xl lg:text-6xl xl:text-[4rem]"
            >
                Encuentra el espacio perfecto para celebrar
            </h1>
            <p
                class="mt-5 max-w-2xl text-lg leading-relaxed text-white/90 sm:text-xl"
            >
                Descubre terrazas, jardines y salones para tu próximo evento.
                Compara opciones, revisa la información y contacta directamente
                al propietario.
            </p>
        </div>
    </section>

    <div class="home-container--search relative z-10 -mt-20 sm:-mt-24">
        <HomeSearch
            :action="urls.index"
            :locations="locations"
            :services="services"
        />
        <p class="mt-4 text-sm font-medium text-[color:var(--home-secondary)]">
            {{ totalListings }}
            {{
                totalListings === 1
                    ? 'espacio publicado'
                    : 'espacios publicados'
            }}
            · Sin costo para el visitante
        </p>
    </div>

    <section class="home-container pt-16 pb-16 sm:pt-24 sm:pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-3xl">
                <p class="home-eyebrow">Catálogo</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    Espacios para tu próxima celebración
                </h2>
            </div>
            <Link
                v-if="listings.length > 0"
                :href="urls.index"
                class="hidden items-center gap-1 text-base font-bold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)] sm:inline-flex"
            >
                Ver todas las terrazas
                <span aria-hidden="true">→</span>
            </Link>
        </div>

        <div
            v-if="listings.length > 0"
            class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <HomeVenueCard
                v-for="listing in listings"
                :key="listing.id"
                :listing="listing"
            />
        </div>

        <div
            v-else
            class="mt-8 rounded-[1.125rem] border border-[color:var(--home-border)] bg-white px-6 py-16 text-center"
        >
            <p class="text-xl font-extrabold sm:text-2xl">
                Todavía no hay espacios publicados
            </p>
            <p
                class="mx-auto mt-3 max-w-md text-base text-[color:var(--home-secondary)]"
            >
                En cuanto un propietario publique su espacio aparecerá aquí.
            </p>
        </div>

        <Link
            v-if="listings.length > 0"
            :href="urls.index"
            class="home-btn home-btn--outline mt-8 w-full sm:hidden"
        >
            Ver todas las terrazas
        </Link>
    </section>

    <section class="home-alt-section">
        <div class="home-container py-14 sm:py-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-3xl">
                    <p class="home-eyebrow">Ubicaciones</p>
                    <h2
                        class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                    >
                        Explora por ubicación
                    </h2>
                </div>
                <Link
                    :href="urls.index"
                    class="text-base font-bold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                >
                    Ver todas
                </Link>
            </div>

            <nav
                class="mt-6 flex [scrollbar-width:none] gap-2 overflow-x-auto pb-2 [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
                aria-label="Ciudades con espacios publicados"
            >
                <Link :href="urls.index" class="home-tab home-tab--active">
                    Todas las ciudades
                </Link>
                <Link
                    v-for="city in cities"
                    :key="`${city.state}-${city.city}`"
                    :href="cityUrl(city)"
                    class="home-tab"
                >
                    {{ city.city }}
                    <span class="text-sm font-semibold opacity-60">
                        {{ city.count }}
                    </span>
                </Link>
            </nav>
        </div>
    </section>

    <section v-if="spaceTypes.length > 0" class="home-container py-16 sm:py-24">
        <div class="max-w-3xl">
            <p class="home-eyebrow">Tipo de espacio</p>
            <h2
                class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
            >
                Encuentra el ambiente que buscas
            </h2>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
            <Link
                v-for="type in spaceTypes"
                :key="type.value"
                :href="type.href"
                class="home-tile"
            >
                <img
                    :src="type.image"
                    :alt="`Espacios para eventos: ${type.title}`"
                    loading="lazy"
                    decoding="async"
                />
                <div class="home-tile-caption">
                    <div>
                        <h3 class="text-lg leading-tight font-bold sm:text-xl">
                            {{ type.title }}
                        </h3>
                        <p
                            class="mt-1 text-sm text-[color:var(--home-secondary)]"
                        >
                            {{ type.copy }}
                        </p>
                    </div>
                    <span
                        class="text-lg text-[color:var(--home-primary-deep)]"
                        aria-hidden="true"
                    >
                        →
                    </span>
                </div>
            </Link>
        </div>
    </section>

    <section class="home-container pb-16 sm:pb-24">
        <div
            class="grid gap-10 border-t border-[color:var(--home-border)] pt-12 lg:grid-cols-[0.7fr_1.5fr] lg:gap-16"
        >
            <div>
                <p class="home-eyebrow">Cómo funciona</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    Reservar es simple
                </h2>
            </div>
            <ol class="grid gap-8 sm:grid-cols-3 sm:gap-6">
                <li v-for="(step, index) in steps" :key="step.title">
                    <p class="home-eyebrow">Paso 0{{ index + 1 }}</p>
                    <h3 class="mt-2 text-lg font-bold">{{ step.title }}</h3>
                    <p
                        class="mt-2 text-base leading-relaxed text-[color:var(--home-secondary)]"
                    >
                        {{ step.copy }}
                    </p>
                </li>
            </ol>
        </div>
    </section>

    <section id="propietarios" class="home-alt-section">
        <div
            class="home-container grid gap-10 py-16 sm:py-20 lg:grid-cols-[1.05fr_1fr] lg:items-center lg:gap-16"
        >
            <div>
                <p class="home-eyebrow">Para propietarios</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    ¿Tienes una terraza o salón para eventos?
                </h2>
                <p
                    class="mt-4 max-w-xl text-lg leading-relaxed text-[color:var(--home-secondary)]"
                >
                    Publica tu espacio, organiza tus solicitudes y conecta con
                    personas que buscan el lugar ideal para su celebración.
                </p>
                <Link href="/propietarios" class="home-btn home-btn--dark mt-8">
                    Publica tu espacio
                </Link>
            </div>

            <div
                class="home-media aspect-[4/3] overflow-hidden rounded-[1.25rem]"
            >
                <img
                    :src="ownerImage"
                    alt="Terraza con alberca frente al mar"
                    loading="lazy"
                    decoding="async"
                />
            </div>
        </div>
    </section>
</template>
