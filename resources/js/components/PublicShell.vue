<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();

const branding = computed(
    () =>
        (page.props.branding ?? {}) as {
            app_name?: string;
            logo_url?: string | null;
            support_email?: string | null;
            legal?: Record<string, string>;
        },
);

const appName = computed(() => branding.value.app_name || 'Terrazas');

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header
            class="border-b border-border/60 bg-background/95 backdrop-blur"
        >
            <div
                class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link href="/terrazas" class="flex items-center gap-2">
                    <img
                        v-if="branding.logo_url"
                        :src="branding.logo_url"
                        :alt="appName"
                        class="size-8 rounded-lg object-cover"
                    />
                    <span
                        v-else
                        class="flex size-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground"
                    >
                        {{ appName.charAt(0) }}
                    </span>
                    <span class="text-lg font-semibold">{{ appName }}</span>
                </Link>

                <nav class="flex items-center gap-2 text-sm">
                    <Link
                        href="/terrazas"
                        class="rounded-md px-3 py-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                    >
                        Explorar
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="dashboardUrl"
                        class="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Mi panel
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            Entrar
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-primary px-3 py-2 font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Publicar terraza
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
            <slot />
        </main>

        <footer class="border-t border-border/60 py-6">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <span>Terrazas para eventos y reuniones.</span>
                <div class="flex flex-wrap items-center gap-3">
                    <a
                        v-if="branding.support_email"
                        :href="`mailto:${branding.support_email}`"
                        class="underline"
                    >
                        Soporte
                    </a>
                    <a
                        v-if="branding.legal?.terms"
                        :href="branding.legal.terms"
                        class="underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Términos
                    </a>
                    <a
                        v-if="branding.legal?.privacy"
                        :href="branding.legal.privacy"
                        class="underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Privacidad
                    </a>
                    <span>Mapas por OpenStreetMap.</span>
                </div>
            </div>
        </footer>
    </div>
</template>
