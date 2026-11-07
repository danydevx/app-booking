<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    Check,
    MapPin,
    Search,
    Send,
    ShieldCheck,
    Sparkles,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';

const page = usePage();

const appName = computed(
    () =>
        (page.props.branding as { app_name?: string } | undefined)?.app_name ??
        'App Booking',
);

const currentTeam = computed(
    () => page.props.currentTeam as { slug: string } | null,
);

const ownerUrl = computed(() =>
    currentTeam.value ? dashboard(currentTeam.value.slug).url : register().url,
);

const ownerLabel = computed(() =>
    currentTeam.value ? 'Ir a mi panel' : 'Publicar mi terraza',
);

const features = [
    {
        icon: Search,
        title: 'Busca por zona',
        description:
            'Filtra por ciudad, capacidad o servicio y ajusta el radio en el mapa.',
    },
    {
        icon: CalendarDays,
        title: 'Consulta disponibilidad',
        description:
            'Revisa las fechas bloqueadas y reservadas antes de enviar tu solicitud.',
    },
    {
        icon: Send,
        title: 'Solicita directo',
        description:
            'Envía tu interés con fechas y datos de contacto; el propietario te responde.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Encuentra tu terraza',
        description:
            'Explora el catálogo, compara opciones y revisa fotos, servicios y ubicación.',
    },
    {
        number: '02',
        title: 'Envía una solicitud',
        description:
            'Indica fechas, número de invitados y tus datos. No necesitas crear una cuenta.',
    },
    {
        number: '03',
        title: 'Coordina con el propietario',
        description:
            'El propietario confirma disponibilidad y acuerdan los detalles del evento.',
    },
];
</script>

<template>
    <Head :title="`${appName} · Terrazas para eventos`" />

    <PublicShell>
        <div class="space-y-16 pb-8 sm:space-y-20">
            <section
                class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center"
            >
                <div class="space-y-6">
                    <Badge variant="secondary" class="gap-1.5">
                        <Sparkles class="size-3.5" />
                        Terrazas para eventos y reuniones
                    </Badge>

                    <div class="space-y-4">
                        <h1
                            class="max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl"
                        >
                            Encuentra la terraza ideal para tu próximo evento
                        </h1>
                        <p
                            class="max-w-xl text-lg text-muted-foreground sm:text-xl"
                        >
                            Busca por ubicación, revisa disponibilidad y envía
                            una solicitud directa al propietario. Sin
                            intermediarios y sin costo para el visitante.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button size="lg" as-child>
                            <Link href="/terrazas">
                                Explorar terrazas
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="ownerUrl">{{ ownerLabel }}</Link>
                        </Button>
                    </div>

                    <ul
                        class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground"
                    >
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Solicitud sin cuenta
                        </li>
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Disponibilidad actualizada
                        </li>
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Contacto directo
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium">
                                Tu próxima terraza
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Todo lo que necesitas antes de reservar
                            </p>
                        </div>
                        <Badge variant="outline">Vista previa</Badge>
                    </div>

                    <div class="space-y-3">
                        <div
                            class="flex items-start gap-3 rounded-lg border bg-background p-3"
                        >
                            <MapPin class="mt-0.5 size-5 text-primary" />
                            <div>
                                <p class="text-sm font-medium">Ubicación</p>
                                <p class="text-xs text-muted-foreground">
                                    Ciudad, radio y mapa con OpenStreetMap
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-lg border bg-background p-3"
                        >
                            <CalendarDays class="mt-0.5 size-5 text-primary" />
                            <div>
                                <p class="text-sm font-medium">
                                    Disponibilidad
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Fechas bloqueadas y reservadas por terraza
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 rounded-lg border bg-background p-3"
                        >
                            <Send class="mt-0.5 size-5 text-primary" />
                            <div>
                                <p class="text-sm font-medium">Solicitud</p>
                                <p class="text-xs text-muted-foreground">
                                    Envía fechas e intereses al propietario
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-5 flex items-center gap-2 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground"
                    >
                        <ShieldCheck class="size-4 shrink-0" />
                        Publicaciones de cuentas activas y moderación de
                        reportes.
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="max-w-2xl space-y-2">
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Todo lo necesario para elegir bien
                    </h2>
                    <p class="text-muted-foreground">
                        Menos mensajes de ida y vuelta, más información antes de
                        contactar.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-xl border bg-card p-5"
                    >
                        <span
                            class="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </span>
                        <h3 class="font-semibold">{{ feature.title }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="rounded-2xl border bg-card p-6 sm:p-8">
                <div class="mb-6 max-w-2xl space-y-2">
                    <h2 class="text-2xl font-semibold tracking-tight">
                        ¿Cómo funciona?
                    </h2>
                    <p class="text-muted-foreground">
                        De la búsqueda a la coordinación en tres pasos.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <article
                        v-for="step in steps"
                        :key="step.number"
                        class="space-y-2"
                    >
                        <span class="text-sm font-semibold text-primary">{{
                            step.number
                        }}</span>
                        <h3 class="font-semibold">{{ step.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="grid gap-6 rounded-2xl border bg-primary/5 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center"
            >
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-primary">
                        <Users class="size-5" />
                        <span class="text-sm font-medium"
                            >Para propietarios</span
                        >
                    </div>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Publica tu terraza y recibe solicitudes
                    </h2>
                    <p class="max-w-2xl text-muted-foreground">
                        Administra disponibilidad, clientes y anticipos desde un
                        solo panel. Empieza gratis y amplía tu plan cuando lo
                        necesites.
                    </p>
                    <ul
                        class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground"
                    >
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Catálogo público
                        </li>
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Agenda y solicitudes
                        </li>
                        <li class="flex items-center gap-1.5">
                            <Check class="size-4 text-primary" />
                            Planes con Stripe
                        </li>
                    </ul>
                </div>

                <Button size="lg" as-child>
                    <Link :href="ownerUrl">
                        {{ ownerLabel }}
                        <ArrowRight class="size-4" />
                    </Link>
                </Button>
            </section>
        </div>
    </PublicShell>
</template>
