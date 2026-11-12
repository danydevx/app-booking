<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login } from '@/routes';

withDefaults(defineProps<{ flush?: boolean }>(), {
    flush: false,
});

const page = usePage();

const branding = computed(
    () =>
        (page.props.branding ?? {}) as {
            app_name?: string;
            support_email?: string | null;
            legal?: Record<string, string>;
        },
);

const appName = computed(() => branding.value.app_name || 'AApp Booking');

const currentTeam = computed(
    () => page.props.currentTeam as { slug: string } | null,
);

const panelUrl = computed(() =>
    currentTeam.value ? dashboard(currentTeam.value.slug).url : '/app',
);

const year = new Date().getFullYear();
</script>

<template>
    <footer class="home-footer" :class="flush ? '' : 'mt-20'">
        <div class="home-container py-12">
            <div class="grid gap-10 md:grid-cols-[1.6fr_1fr_1fr_1fr]">
                <div class="max-w-xs space-y-3">
                    <Link href="/" class="inline-flex items-center gap-2.5">
                        <span
                            class="flex size-8 items-center justify-center rounded-[0.625rem] bg-[color:var(--home-primary)] text-sm font-extrabold text-white"
                            aria-hidden="true"
                        >
                            A
                        </span>
                        <span class="text-[1rem] font-extrabold tracking-tight">
                            {{ appName }}
                        </span>
                    </Link>
                    <p
                        class="text-[0.9375rem] leading-relaxed text-[color:var(--home-secondary)]"
                    >
                        Descubre terrazas, jardines y salones para celebrar
                        cumpleaños, bodas, reuniones y eventos de empresa.
                    </p>
                </div>

                <div class="space-y-4 text-[0.9375rem]">
                    <p
                        class="text-[0.8125rem] font-bold tracking-[0.12em] text-[color:var(--home-dark)] uppercase"
                    >
                        Explorar
                    </p>
                    <ul class="space-y-2 text-[color:var(--home-muted)]">
                        <li>
                            <Link
                                href="/terrazas"
                                class="hover:text-[color:var(--home-primary-deep)]"
                                >Todas las terrazas</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/terrazas?service=pool"
                                class="hover:text-[color:var(--home-primary-deep)]"
                                >Con alberca</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/terrazas?service=garden"
                                class="hover:text-[color:var(--home-primary-deep)]"
                                >Con jardín</Link
                            >
                        </li>
                    </ul>
                </div>

                <div class="space-y-4 text-[0.9375rem]">
                    <p
                        class="text-[0.8125rem] font-bold tracking-[0.12em] text-[color:var(--home-dark)] uppercase"
                    >
                        Cuenta
                    </p>
                    <ul class="space-y-2 text-[color:var(--home-muted)]">
                        <li>
                            <Link
                                :href="
                                    page.props.auth?.user ? panelUrl : login()
                                "
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                {{
                                    page.props.auth?.user
                                        ? 'Mi panel'
                                        : 'Iniciar sesión'
                                }}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/propietarios"
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                Publica tu espacio
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="space-y-4 text-[0.9375rem]">
                    <p
                        class="text-[0.8125rem] font-bold tracking-[0.12em] text-[color:var(--home-dark)] uppercase"
                    >
                        Legales
                    </p>
                    <ul class="space-y-2 text-[color:var(--home-muted)]">
                        <li v-if="branding.legal?.terms">
                            <a
                                :href="branding.legal.terms"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                Términos del servicio
                            </a>
                        </li>
                        <li v-if="branding.legal?.privacy">
                            <a
                                :href="branding.legal.privacy"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                Aviso de privacidad
                            </a>
                        </li>
                        <li v-if="branding.support_email">
                            <a
                                :href="`mailto:${branding.support_email}`"
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                Soporte
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://unsplash.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:text-[color:var(--home-primary-deep)]"
                            >
                                Fotografías: Unsplash
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-2 border-t border-[color:var(--home-border)] pt-6 text-[0.8125rem] text-[color:var(--home-muted)] sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    © {{ year }} {{ appName }}. Todos los derechos reservados.
                </p>
                <p>Mapas por OpenStreetMap.</p>
            </div>
        </div>
    </footer>
</template>
