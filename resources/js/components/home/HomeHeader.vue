<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();

const branding = computed(
    () => (page.props.branding as { app_name?: string } | undefined) ?? {},
);

const appName = computed(() => branding.value.app_name || 'AApp Booking');

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const currentTeam = computed(
    () => page.props.currentTeam as { slug: string } | null,
);

const panelUrl = computed(() =>
    currentTeam.value ? dashboard(currentTeam.value.slug).url : '/app',
);

const onOwnersPage = computed(() => page.url.startsWith('/propietarios'));

const props = withDefaults(defineProps<{ wide?: boolean }>(), {
    wide: false,
});

const containerClass = computed(() =>
    props.wide ? 'w-full px-4 sm:px-6 lg:px-8' : 'home-container',
);

const mobileOpen = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-[color:var(--home-border)] bg-white/95 backdrop-blur"
    >
        <div
            :class="[
                containerClass,
                'flex h-[4.25rem] items-center justify-between gap-6',
            ]"
        >
            <Link
                href="/"
                class="flex items-center gap-2.5"
                aria-label="Inicio"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-[0.75rem] bg-[color:var(--home-primary)] text-[1rem] font-extrabold text-white"
                    aria-hidden="true"
                >
                    A
                </span>
                <span
                    class="text-[1.0625rem] font-extrabold tracking-tight text-[color:var(--home-dark)]"
                >
                    {{ appName }}
                </span>
            </Link>

            <nav
                class="hidden items-center gap-7 md:flex"
                aria-label="Principal"
            >
                <Link href="/terrazas" class="home-nav-link">
                    Explorar terrazas
                </Link>
                <Link href="/propietarios" class="home-nav-link">
                    Para propietarios
                </Link>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <template v-if="isAuthenticated">
                    <Link
                        :href="panelUrl"
                        class="home-btn home-btn--outline home-btn--sm"
                    >
                        Mi panel
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="home-btn home-btn--outline home-btn--sm"
                    >
                        Iniciar sesión
                    </Link>
                    <Link
                        :href="onOwnersPage ? register() : '/propietarios'"
                        class="home-btn home-btn--dark home-btn--sm"
                    >
                        {{
                            onOwnersPage
                                ? 'Comenzar gratis'
                                : 'Publica tu espacio'
                        }}
                    </Link>
                </template>
            </div>

            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-[0.75rem] border border-[color:var(--home-border)] text-[color:var(--home-dark)] md:hidden"
                :aria-expanded="mobileOpen"
                aria-controls="home-mobile-nav"
                :aria-label="mobileOpen ? 'Cerrar menú' : 'Abrir menú'"
                @click="mobileOpen = !mobileOpen"
            >
                <svg
                    v-if="!mobileOpen"
                    width="18"
                    height="14"
                    viewBox="0 0 18 14"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M1 1h16M1 7h16M1 13h16"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                    />
                </svg>
                <svg
                    v-else
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M2 2l12 12M14 2L2 14"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
        </div>

        <div
            v-if="mobileOpen"
            id="home-mobile-nav"
            class="border-t border-[color:var(--home-border)] bg-white md:hidden"
        >
            <nav
                :class="[containerClass, 'flex flex-col gap-1 py-4']"
                aria-label="Móvil"
            >
                <Link
                    href="/terrazas"
                    class="rounded-[0.75rem] px-3 py-2.5 text-[0.9375rem] font-semibold text-[color:var(--home-dark)] hover:bg-[#fafafa]"
                    @click="mobileOpen = false"
                >
                    Explorar terrazas
                </Link>
                <Link
                    href="/propietarios"
                    class="rounded-[0.75rem] px-3 py-2.5 text-[0.9375rem] font-semibold text-[color:var(--home-dark)] hover:bg-[#fafafa]"
                    @click="mobileOpen = false"
                >
                    Para propietarios
                </Link>

                <div
                    class="mt-2 grid gap-2 border-t border-[color:var(--home-border)] pt-3"
                >
                    <template v-if="isAuthenticated">
                        <Link
                            :href="panelUrl"
                            class="home-btn home-btn--outline home-btn--sm"
                        >
                            Mi panel
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="home-btn home-btn--outline home-btn--sm"
                        >
                            Iniciar sesión
                        </Link>
                        <Link
                            :href="onOwnersPage ? register() : '/propietarios'"
                            class="home-btn home-btn--dark home-btn--sm"
                        >
                            {{
                                onOwnersPage
                                    ? 'Comenzar gratis'
                                    : 'Publica tu espacio'
                            }}
                        </Link>
                    </template>
                </div>
            </nav>
        </div>
    </header>
</template>
