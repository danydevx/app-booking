<script setup lang="ts">
import '@/homeSite';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FeatureShowcaseSlider from '@/components/public/FeatureShowcaseSlider.vue';
import FeatureIcon from '@/components/public/FeatureIcon.vue';
import type { FeatureIconName } from '@/components/public/featureIcon';
import type { FeatureSlide } from '@/components/public/featureShowcase';

type OwnerPlan = {
    slug: string;
    name: string;
    description: string;
    price_cents: number;
    price_formatted: string;
    period: string;
    interval: string;
    limits: {
        listings: number;
        photos_per_listing: number;
        videos_per_listing: number;
    };
    features: {
        customers: boolean;
    };
    is_free: boolean;
    active: boolean;
};

const props = defineProps<{
    plans: OwnerPlan[];
    urls: {
        register: string;
        login: string;
        dashboard: string | null;
        billing: string | null;
    };
}>();

const page = usePage();

const authenticated = computed(() => Boolean(page.props.auth?.user));

const freePlan = computed(
    () => props.plans.find((plan) => plan.is_free) ?? null,
);
const proPlan = computed(
    () => props.plans.find((plan) => !plan.is_free) ?? null,
);

const startUrl = computed(() =>
    authenticated.value
        ? (props.urls.dashboard ?? props.urls.register)
        : props.urls.register,
);
const proUrl = computed(() =>
    authenticated.value
        ? (props.urls.billing ?? props.urls.dashboard ?? props.urls.register)
        : props.urls.register,
);

const startLabel = computed(() =>
    authenticated.value ? 'Ir a mi panel' : 'Comenzar gratis',
);
const proLabel = computed(() =>
    authenticated.value ? 'Administrar plan' : 'Elegir Pro',
);

const hasCrm = computed(() => proPlan.value?.features.customers === true);

const showFreeNote = computed(
    () =>
        freePlan.value !== null &&
        freePlan.value.active &&
        freePlan.value.price_cents === 0 &&
        freePlan.value.limits.listings > 0,
);

const freeListingsLabel = computed(() => {
    const count = freePlan.value?.limits.listings ?? 1;

    return `${count} ${count === 1 ? 'terraza' : 'terrazas'}`;
});

const freePhotosLabel = computed(() => {
    const count = freePlan.value?.limits.photos_per_listing ?? 5;

    return `${count} ${count === 1 ? 'fotografía' : 'fotografías'}`;
});

const crmSlides: FeatureSlide[] = [
    {
        id: 'clientes',
        eyebrow: 'Clientes',
        icon: 'users',
        title: 'Clientes organizados',
        features: [
            'Base de clientes con búsqueda y filtros.',
            'Etiquetas y segmentos para ordenar tu cartera.',
            'Historial de interacciones y notas.',
            'Ficha 360 con solicitudes, reservas, cotizaciones y contratos.',
        ],
        // Captura pendiente: /images/showcase/crm/clientes.webp
        image: null,
        imageAlt: 'Listado y ficha de clientes del CRM',
    },
    {
        id: 'seguimiento',
        eyebrow: 'Seguimiento',
        icon: 'kanban',
        title: 'Seguimiento comercial',
        features: [
            'Pipeline por etapas para cada oportunidad.',
            'Tareas con recordatorios y responsables.',
            'Tablero Hoy para saber qué atender primero.',
            'Asignación de clientes al equipo.',
        ],
        // Captura pendiente: /images/showcase/crm/seguimiento.webp
        image: null,
        imageAlt: 'Pipeline y tablero Hoy del CRM',
    },
    {
        id: 'cotizaciones',
        eyebrow: 'Cotizaciones',
        icon: 'signature',
        title: 'Cotizaciones y contratos',
        features: [
            'Crea y envía cotizaciones desde la ficha del cliente.',
            'Aceptación o rechazo mediante enlace público.',
            'Contratos con publicación y aceptación de términos.',
        ],
        // Captura pendiente: /images/showcase/crm/cotizaciones.webp
        image: null,
        imageAlt: 'Cotizador y contrato del CRM',
    },
    {
        id: 'comunicacion',
        eyebrow: 'Comunicación',
        icon: 'chart-column',
        title: 'Comunicación y reportes',
        features: [
            'Plantillas de correo y envíos desde el CRM.',
            'Historial de interacciones con cada cliente.',
            'Reportes y métricas comerciales.',
            'Atribución de leads para saber de dónde llegan.',
        ],
        // Captura pendiente: /images/showcase/crm/reportes.webp
        image: null,
        imageAlt: 'Reportes y métricas comerciales del CRM',
    },
    {
        id: 'experiencia',
        eyebrow: 'Experiencia',
        icon: 'heart-handshake',
        title: 'Experiencia del cliente',
        features: [
            'Portal del cliente con la información de su evento.',
            'Opiniones y reseñas mediante enlace público.',
        ],
        // Captura pendiente: /images/showcase/crm/portal-cliente.webp
        image: null,
        imageAlt: 'Portal del cliente y opiniones',
    },
];

type ToolItem = {
    icon: FeatureIconName;
    label: string;
    pro?: boolean;
};

type OperationItem = {
    icon: FeatureIconName;
    label: string;
    tier: 'Gratis' | 'Pro';
};

const publishTools: ToolItem[] = [
    {
        icon: 'globe',
        label: 'Publicación en el directorio de terrazas, jardines y salones.',
    },
    {
        icon: 'images',
        label: 'Galería de fotografías, características y capacidad.',
    },
    {
        icon: 'calendar-days',
        label: 'Calendario y disponibilidad visibles para tus clientes.',
    },
    {
        icon: 'inbox',
        label: 'Solicitudes con los datos de contacto y fechas del evento.',
    },
];

const manageTools: ToolItem[] = [
    {
        icon: 'layout-dashboard',
        label: 'Bloqueo de fechas, reservas manuales y panel de administración.',
    },
    {
        icon: 'users',
        label: 'CRM de clientes, pipeline y tareas.',
        pro: true,
    },
    {
        icon: 'file-text',
        label: 'Cotizaciones, contratos y correos.',
        pro: true,
    },
    {
        icon: 'chart-column',
        label: 'Reportes comerciales y portal del cliente.',
        pro: true,
    },
];

const visibilityItems: ToolItem[] = [
    {
        icon: 'globe',
        label: 'Directorio público de espacios para eventos.',
    },
    {
        icon: 'images',
        label: 'Galería de fotografías y características del lugar.',
    },
    {
        icon: 'inbox',
        label: 'Solicitudes con fechas e invitados.',
    },
    {
        icon: 'calendar-days',
        label: 'Disponibilidad publicada por ti.',
    },
];

const operationItems: OperationItem[] = [
    {
        icon: 'calendar-days',
        label: 'Calendario de disponibilidad y bloqueos de fechas.',
        tier: 'Gratis',
    },
    {
        icon: 'clipboard-list',
        label: 'Gestión de solicitudes y reservas manuales.',
        tier: 'Gratis',
    },
    {
        icon: 'kanban',
        label: 'CRM, tareas y seguimiento de oportunidades.',
        tier: 'Pro',
    },
    {
        icon: 'file-text',
        label: 'Cotizaciones, contratos y reportes.',
        tier: 'Pro',
    },
];

const steps: Array<{
    icon: FeatureIconName;
    number: string;
    title: string;
    description: string;
}> = [
    {
        icon: 'store',
        number: '01',
        title: 'Publica tu espacio',
        description:
            'Crea tu cuenta y agrega la información y fotografías de tu terraza.',
    },
    {
        icon: 'inbox',
        number: '02',
        title: 'Recibe solicitudes',
        description:
            'Los visitantes encuentran tu espacio y pueden enviarte solicitudes.',
    },
    {
        icon: 'layout-dashboard',
        number: '03',
        title: 'Organiza tu negocio',
        description:
            'Gestiona disponibilidad y reservas. Cuando necesites herramientas comerciales avanzadas, puedes contratar Pro.',
    },
];

const comparisonRows = computed(() => {
    const free = freePlan.value;
    const pro = proPlan.value;

    if (!free || !pro) {
        return [];
    }

    return [
        {
            label: 'Precio',
            free: free.price_formatted,
            pro: `${pro.price_formatted} / ${pro.period}`,
        },
        {
            label: 'Terrazas',
            free: free.limits.listings,
            pro: pro.limits.listings,
        },
        {
            label: 'Fotos por terraza',
            free: free.limits.photos_per_listing,
            pro: pro.limits.photos_per_listing,
        },
        {
            label: 'Videos por terraza',
            free: free.limits.videos_per_listing,
            pro: pro.limits.videos_per_listing,
        },
        { label: 'Directorio público', free: 'Sí', pro: 'Sí' },
        { label: 'Solicitudes', free: 'Sí', pro: 'Sí' },
        { label: 'Reservas manuales', free: 'Sí', pro: 'Sí' },
        { label: 'Calendario de disponibilidad', free: 'Sí', pro: 'Sí' },
        {
            label: 'CRM de clientes',
            free: 'No',
            pro: hasCrm.value ? 'Sí' : 'No',
        },
        { label: 'Pipeline', free: 'No', pro: hasCrm.value ? 'Sí' : 'No' },
        { label: 'Tareas', free: 'No', pro: hasCrm.value ? 'Sí' : 'No' },
        { label: 'Cotizaciones', free: 'No', pro: hasCrm.value ? 'Sí' : 'No' },
        { label: 'Contratos', free: 'No', pro: hasCrm.value ? 'Sí' : 'No' },
        { label: 'Reportes CRM', free: 'No', pro: hasCrm.value ? 'Sí' : 'No' },
        {
            label: 'Portal del cliente',
            free: 'No',
            pro: hasCrm.value ? 'Sí' : 'No',
        },
    ];
});

const faqs = computed(() => [
    {
        question: '¿Puedo comenzar gratis?',
        answer: `Sí. El plan Gratis te permite publicar ${freeListingsLabel.value} con hasta ${freePhotosLabel.value}, recibir solicitudes, gestionar tu disponibilidad y registrar reservas manuales.`,
    },
    {
        question: '¿Necesito pagar para recibir solicitudes?',
        answer: 'No. La recepción y gestión de solicitudes está incluida en el plan Gratis.',
    },
    {
        question: '¿Qué incluye el CRM Pro?',
        answer: 'Clientes, historial, etiquetas, pipeline, tareas y recordatorios, cotizaciones, contratos, correos, reportes, portal del cliente y seguimiento comercial.',
    },
    {
        question: '¿Puedo publicar varias terrazas?',
        answer: `Sí. El plan Pro permite hasta ${proPlan.value?.limits.listings ?? 5} terrazas con más fotografías y videos por espacio.`,
    },
    {
        question: '¿Puedo cambiar de plan?',
        answer: 'Sí. Puedes contratar Pro desde Facturación y, si ya lo tienes, administrar o cancelar la suscripción desde el portal de facturación. No hay prueba gratuita de Pro.',
    },
    {
        question: '¿La plataforma garantiza reservas?',
        answer: 'No. AApp Booking te ayuda a mostrar tu espacio y recibir solicitudes; las reservas dependen de tu disponibilidad, tu propuesta y la decisión de cada cliente.',
    },
]);

const unsplash = (id: string, width = 1200): string =>
    `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${width}&q=80`;

const heroImage = unsplash('1561501900-3701fa6a0864', 1800);
const visibilityImage = unsplash('1519225421980-715cb0215aed', 1200);
const operationsImage = unsplash('1540541338287-41700207dee6', 1200);
</script>

<template>
    <Head title="Publica tu terraza">
        <meta
            name="description"
            content="Publica tu terraza, jardín o salón, recibe solicitudes y administra disponibilidad, reservas y clientes desde una sola plataforma."
        />
        <meta property="og:type" content="website" />
        <meta
            property="og:title"
            content="Tu terraza es un negocio. Adminístrala como tal."
        />
        <meta
            property="og:description"
            content="Publica tu espacio, recibe solicitudes, organiza tus reservas y administra tus clientes con AApp Booking."
        />
        <meta property="og:image" :content="heroImage" />
    </Head>

    <section class="home-container py-12 sm:py-16 lg:py-20">
        <div
            class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-16"
        >
            <div>
                <p class="home-eyebrow">Para propietarios y administradores</p>
                <h1
                    class="mt-3 max-w-3xl text-[2.5rem] leading-[1.06] font-extrabold text-[color:var(--home-dark)] sm:text-5xl lg:text-[3.5rem]"
                >
                    Tu terraza es un negocio. Adminístrala como tal.
                </h1>
                <p
                    class="mt-5 max-w-2xl text-lg leading-relaxed text-[color:var(--home-secondary)]"
                >
                    Publica tu espacio, recibe solicitudes, organiza tus
                    reservas y administra tus clientes desde una sola
                    plataforma.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <Link :href="startUrl" class="home-btn home-btn--dark">
                        {{ startLabel }}
                    </Link>
                    <a href="#herramientas" class="home-btn home-btn--outline">
                        Conoce las herramientas
                    </a>
                </div>
                <p
                    v-if="showFreeNote"
                    class="mt-4 flex items-center gap-2 text-sm font-medium text-[color:var(--home-secondary)]"
                >
                    <FeatureIcon name="check" size="sm" variant="plain" />
                    Publica tu primera terraza gratis.
                </p>
                <p
                    v-if="!authenticated"
                    class="mt-3 text-sm text-[color:var(--home-muted)]"
                >
                    ¿Ya tienes cuenta?
                    <a
                        :href="urls.login"
                        class="font-semibold text-[color:var(--home-primary-deep)] hover:text-[color:var(--home-primary-hover)]"
                    >
                        Inicia sesión
                    </a>
                </p>
            </div>

            <div
                class="home-media aspect-[4/3] overflow-hidden rounded-[1.25rem]"
            >
                <img
                    :src="heroImage"
                    alt="Terraza preparada para un evento con mesas y sombrillas"
                    fetchpriority="high"
                />
            </div>
        </div>
    </section>

    <section
        id="herramientas"
        class="border-t border-[color:var(--home-border)] bg-white"
    >
        <div class="home-container py-16 sm:py-20">
            <div class="max-w-3xl">
                <p class="home-eyebrow">Todo en un solo lugar</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    Todo tu negocio, mejor organizado
                </h2>
                <p
                    class="mt-4 text-lg leading-relaxed text-[color:var(--home-secondary)]"
                >
                    Desde la publicación de tu espacio hasta el seguimiento de
                    cada cliente. Empieza con las herramientas básicas y suma el
                    CRM cuando tu negocio lo necesite.
                </p>
            </div>

            <div
                class="mt-10 grid gap-10 border-t border-[color:var(--home-border)] pt-8 lg:grid-cols-2 lg:gap-16"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <FeatureIcon name="store" size="md" variant="primary" />
                        <p class="home-eyebrow">Publica y recibe solicitudes</p>
                    </div>
                    <h3 class="mt-2 text-xl font-extrabold">
                        Tu espacio, visible para quien lo busca
                    </h3>
                    <ul
                        class="mt-4 space-y-3 text-base text-[color:var(--home-secondary)]"
                    >
                        <li
                            v-for="item in publishTools"
                            :key="item.label"
                            class="flex items-start gap-2.5 border-b border-[color:var(--home-border)] pb-3 last:border-0 last:pb-0"
                        >
                            <FeatureIcon
                                :name="item.icon"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>{{ item.label }}</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="flex items-center gap-3">
                        <FeatureIcon
                            name="layout-dashboard"
                            size="md"
                            variant="primary"
                        />
                        <p class="home-eyebrow">Gestiona y da seguimiento</p>
                    </div>
                    <h3 class="mt-2 text-xl font-extrabold">
                        Tu operación diaria, bajo control
                    </h3>
                    <ul
                        class="mt-4 space-y-3 text-base text-[color:var(--home-secondary)]"
                    >
                        <li
                            v-for="item in manageTools"
                            :key="item.label"
                            class="flex items-start justify-between gap-3 border-b border-[color:var(--home-border)] pb-3 last:border-0 last:pb-0"
                        >
                            <span class="flex items-start gap-2.5">
                                <FeatureIcon
                                    :name="item.icon"
                                    size="sm"
                                    variant="plain"
                                    class="mt-0.5"
                                />
                                <span>{{ item.label }}</span>
                            </span>
                            <span v-if="item.pro" class="home-chip shrink-0">
                                PRO
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="home-container py-16 sm:py-24">
        <div
            class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-16"
        >
            <div>
                <p class="home-eyebrow">Visibilidad</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    Más visibilidad para tu espacio
                </h2>
                <p
                    class="mt-4 text-lg leading-relaxed text-[color:var(--home-secondary)]"
                >
                    Publica fotografías, características y disponibilidad para
                    que las personas encuentren tu terraza y te envíen
                    solicitudes.
                </p>
                <ul
                    class="mt-6 grid gap-3 text-base text-[color:var(--home-secondary)] sm:grid-cols-2"
                >
                    <li
                        v-for="item in visibilityItems"
                        :key="item.label"
                        class="flex items-start gap-2.5 border-t border-[color:var(--home-border)] pt-3"
                    >
                        <FeatureIcon
                            :name="item.icon"
                            size="sm"
                            variant="plain"
                            class="mt-0.5"
                        />
                        <span>{{ item.label }}</span>
                    </li>
                </ul>
                <p
                    v-if="freePlan"
                    class="mt-5 text-sm font-medium text-[color:var(--home-muted)]"
                >
                    El plan Gratis incluye {{ freeListingsLabel }} con hasta
                    {{ freePhotosLabel }}.
                </p>
            </div>

            <div
                class="home-media aspect-[4/3] overflow-hidden rounded-[1.25rem]"
            >
                <img
                    :src="visibilityImage"
                    alt="Mesa larga preparada para una celebración en un jardín"
                    loading="lazy"
                    decoding="async"
                />
            </div>
        </div>
    </section>

    <section class="home-alt-section">
        <div class="home-container py-16 sm:py-24">
            <div
                class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:gap-16"
            >
                <div
                    class="home-media aspect-[4/3] overflow-hidden rounded-[1.25rem]"
                >
                    <img
                        :src="operationsImage"
                        alt="Terraza con alberca preparada para eventos"
                        loading="lazy"
                        decoding="async"
                    />
                </div>

                <div>
                    <p class="home-eyebrow">Operación</p>
                    <h2
                        class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                    >
                        Menos desorden. Más control de tus eventos.
                    </h2>
                    <p
                        class="mt-4 text-lg leading-relaxed text-[color:var(--home-secondary)]"
                    >
                        Organiza la disponibilidad, atiende solicitudes y
                        registra reservas desde el mismo panel. Cuando el
                        volumen crece, el CRM Pro te ayuda a seguir cada
                        oportunidad.
                    </p>
                    <ul
                        class="mt-6 space-y-3 text-base text-[color:var(--home-secondary)]"
                    >
                        <li
                            v-for="item in operationItems"
                            :key="item.label"
                            class="flex items-start justify-between gap-4 border-b border-[color:var(--home-border)] pb-3 last:border-0 last:pb-0"
                        >
                            <span class="flex items-start gap-2.5">
                                <FeatureIcon
                                    :name="item.icon"
                                    size="sm"
                                    variant="plain"
                                    class="mt-0.5"
                                />
                                <span>{{ item.label }}</span>
                            </span>
                            <span class="home-chip shrink-0">
                                {{ item.tier }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section v-if="hasCrm" class="home-container py-16 sm:py-24">
        <div class="max-w-4xl">
            <span
                class="home-chip border-[color:var(--home-primary)]/40 bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]"
            >
                Plan Pro
            </span>
            <h2
                class="mt-4 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
            >
                Convierte cada solicitud en una oportunidad de negocio.
            </h2>
            <p
                class="mt-4 text-lg leading-relaxed text-[color:var(--home-secondary)]"
            >
                Con el CRM Pro puedes organizar tus clientes, dar seguimiento a
                cada oportunidad y gestionar el proceso comercial desde el
                primer contacto hasta la contratación.
            </p>
        </div>

        <div class="mt-10">
            <FeatureShowcaseSlider
                :slides="crmSlides"
                aria-label="Funcionalidades del CRM Pro"
            />
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <Link :href="proUrl" class="home-btn home-btn--dark">
                {{ proLabel }}
            </Link>
            <p class="text-sm text-[color:var(--home-muted)]">
                El CRM, las cotizaciones, los contratos y los reportes forman
                parte del plan Pro. WhatsApp se abre manualmente; no hay envío
                automatizado.
            </p>
        </div>
    </section>

    <section class="home-container py-16 sm:py-24">
        <div class="max-w-3xl">
            <p class="home-eyebrow">Cómo funciona</p>
            <h2
                class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
            >
                De la publicación al seguimiento
            </h2>
        </div>

        <ol
            class="mt-8 grid gap-8 border-t border-[color:var(--home-border)] pt-8 sm:grid-cols-3"
        >
            <li v-for="step in steps" :key="step.number">
                <div class="flex items-center gap-3">
                    <FeatureIcon
                        :name="step.icon"
                        size="md"
                        variant="primary"
                    />
                    <p
                        class="text-sm font-extrabold text-[color:var(--home-primary-deep)]"
                    >
                        {{ step.number }}
                    </p>
                </div>
                <h3 class="mt-2 text-lg font-extrabold">{{ step.title }}</h3>
                <p
                    class="mt-2 text-base leading-relaxed text-[color:var(--home-secondary)]"
                >
                    {{ step.description }}
                </p>
            </li>
        </ol>
    </section>

    <section
        id="planes"
        class="border-t border-[color:var(--home-border)] bg-white"
    >
        <div class="home-container py-16 sm:py-24">
            <div class="max-w-3xl">
                <p class="home-eyebrow">Planes</p>
                <h2
                    class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    Empieza gratis y crece cuando lo necesites
                </h2>
                <p class="mt-4 text-base text-[color:var(--home-secondary)]">
                    Precios en MXN. El plan Pro se cobra mensualmente.
                </p>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-2">
                <article
                    v-if="freePlan"
                    class="flex flex-col rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-extrabold">
                                {{ freePlan.name }}
                            </h3>
                            <p
                                class="mt-1 text-sm text-[color:var(--home-secondary)]"
                            >
                                {{ freePlan.description }}
                            </p>
                        </div>
                        <span class="home-chip">Para empezar</span>
                    </div>
                    <p class="mt-5 text-3xl font-extrabold">
                        {{ freePlan.price_formatted }}
                        <span
                            class="text-sm font-medium text-[color:var(--home-muted)]"
                        >
                            sin costo
                        </span>
                    </p>
                    <ul
                        class="mt-5 space-y-2 text-base text-[color:var(--home-secondary)]"
                    >
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                {{ freePlan.limits.listings }} terraza para
                                publicar.
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                {{ freePlan.limits.photos_per_listing }}
                                fotografías por terraza.
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                Directorio público, solicitudes, calendario y
                                reservas manuales.
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="minus"
                                size="sm"
                                variant="muted"
                                class="mt-0.5"
                            />
                            <span>
                                Sin CRM ni herramientas comerciales avanzadas.
                            </span>
                        </li>
                    </ul>
                    <Link
                        :href="startUrl"
                        class="home-btn home-btn--outline mt-6 w-full"
                    >
                        {{ startLabel }}
                    </Link>
                </article>

                <article
                    v-if="proPlan"
                    class="flex flex-col rounded-[1.125rem] border border-[color:var(--home-primary)]/40 bg-white p-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-extrabold">
                                {{ proPlan.name }}
                            </h3>
                            <p
                                class="mt-1 text-sm text-[color:var(--home-secondary)]"
                            >
                                {{ proPlan.description }}
                            </p>
                        </div>
                        <span
                            class="home-chip border-[color:var(--home-primary)]/40 bg-[color:var(--home-soft)] text-[color:var(--home-primary-deep)]"
                        >
                            CRM Pro
                        </span>
                    </div>
                    <p class="mt-5 text-3xl font-extrabold">
                        {{ proPlan.price_formatted }}
                        <span
                            class="text-sm font-medium text-[color:var(--home-muted)]"
                        >
                            / {{ proPlan.period }}
                        </span>
                    </p>
                    <ul
                        class="mt-5 space-y-2 text-base text-[color:var(--home-secondary)]"
                    >
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                {{ proPlan.limits.listings }} terrazas y
                                {{ proPlan.limits.photos_per_listing }} fotos
                                por terraza.
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                {{ proPlan.limits.videos_per_listing }} videos
                                por terraza.
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>Todo lo del plan Gratis.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FeatureIcon
                                name="check"
                                size="sm"
                                variant="plain"
                                class="mt-0.5"
                            />
                            <span>
                                CRM, pipeline, tareas, cotizaciones, contratos,
                                reportes y portal del cliente.
                            </span>
                        </li>
                    </ul>
                    <Link
                        :href="proUrl"
                        class="home-btn home-btn--primary mt-6 w-full"
                    >
                        {{ proLabel }}
                    </Link>
                    <p class="mt-3 text-xs text-[color:var(--home-muted)]">
                        {{
                            authenticated
                                ? 'Administra tu plan desde Facturación.'
                                : 'Crea tu cuenta y elige Pro desde Facturación.'
                        }}
                    </p>
                </article>
            </div>

            <div
                class="mt-10 overflow-x-auto rounded-[1.125rem] border border-[color:var(--home-border)]"
            >
                <table class="w-full min-w-[36rem] border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-b border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] text-left"
                        >
                            <th
                                class="px-4 py-3 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                            >
                                Comparativa
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                            >
                                Gratis
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-bold tracking-[0.08em] text-[color:var(--home-muted)] uppercase"
                            >
                                Pro
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in comparisonRows"
                            :key="row.label"
                            class="border-b border-[color:var(--home-border)] last:border-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ row.label }}
                            </td>
                            <td
                                class="px-4 py-3 text-[color:var(--home-secondary)]"
                            >
                                <span
                                    v-if="
                                        row.free === 'Sí' || row.free === 'No'
                                    "
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <FeatureIcon
                                        :name="
                                            row.free === 'Sí'
                                                ? 'check'
                                                : 'minus'
                                        "
                                        size="sm"
                                        :variant="
                                            row.free === 'Sí'
                                                ? 'plain'
                                                : 'muted'
                                        "
                                    />
                                    <span>{{ row.free }}</span>
                                </span>
                                <span v-else>{{ row.free }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold">
                                <span
                                    v-if="row.pro === 'Sí' || row.pro === 'No'"
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <FeatureIcon
                                        :name="
                                            row.pro === 'Sí' ? 'check' : 'minus'
                                        "
                                        size="sm"
                                        :variant="
                                            row.pro === 'Sí' ? 'plain' : 'muted'
                                        "
                                    />
                                    <span>{{ row.pro }}</span>
                                </span>
                                <span v-else>{{ row.pro }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-[color:var(--home-muted)]">
                Los límites y funciones pueden actualizarse. El plan Pro no
                incluye una prueba gratuita.
            </p>
        </div>
    </section>

    <section class="home-container py-16 sm:py-24">
        <div class="max-w-3xl">
            <div class="flex items-center gap-3">
                <FeatureIcon
                    name="circle-question-mark"
                    size="md"
                    variant="primary"
                />
                <p class="home-eyebrow">Preguntas frecuentes</p>
            </div>
            <h2
                class="mt-3 text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
            >
                Lo que suelen preguntarnos
            </h2>
        </div>

        <div class="mt-8 border-t border-[color:var(--home-border)]">
            <details
                v-for="faq in faqs"
                :key="faq.question"
                class="group border-b border-[color:var(--home-border)]"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-base font-bold marker:content-none"
                >
                    {{ faq.question }}
                    <span
                        class="text-xl font-normal text-[color:var(--home-muted)] transition-transform group-open:rotate-45"
                        aria-hidden="true"
                    >
                        +
                    </span>
                </summary>
                <p
                    class="pb-5 text-base leading-relaxed text-[color:var(--home-secondary)]"
                >
                    {{ faq.answer }}
                </p>
            </details>
        </div>
    </section>

    <section class="home-alt-section">
        <div class="home-container py-16 sm:py-20">
            <div
                class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-start gap-4">
                    <span class="mt-1 hidden sm:block">
                        <FeatureIcon
                            name="rocket"
                            size="lg"
                            variant="primary"
                        />
                    </span>
                    <div class="max-w-2xl">
                        <h2
                            class="text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                        >
                            Empieza a organizar mejor tu negocio desde hoy.
                        </h2>
                        <p
                            class="mt-3 text-lg text-[color:var(--home-secondary)]"
                        >
                            Publica tu primera terraza gratis y descubre las
                            herramientas de AApp Booking.
                        </p>
                    </div>
                </div>
                <Link :href="startUrl" class="home-btn home-btn--dark shrink-0">
                    {{
                        authenticated ? 'Ir a mi panel' : 'Crear cuenta gratis'
                    }}
                </Link>
            </div>
        </div>
    </section>
</template>
