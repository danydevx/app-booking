<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();

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
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground"
                    >
                        T
                    </span>
                    <span class="text-lg font-semibold">Terrazas</span>
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
                <span>Mapas por OpenStreetMap.</span>
            </div>
        </footer>
    </div>
</template>
