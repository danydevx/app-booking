<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    Flag,
    LayoutDashboard,
    Palette,
    Shield,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { dashboard } from '@/routes';

const page = usePage();

const currentUrl = computed(
    () => new URL(page.url, 'http://localhost').pathname,
);

const appUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const navigation = [
    { title: 'Resumen', href: '/platform', icon: LayoutDashboard },
    { title: 'Cuentas', href: '/platform/accounts', icon: Users },
    { title: 'Usuarios', href: '/platform/users', icon: UserCog },
    { title: 'Terrazas', href: '/platform/listings', icon: Building2 },
    {
        title: 'Suscripciones',
        href: '/platform/subscriptions',
        icon: CreditCard,
    },
    { title: 'Reportes', href: '/platform/reports', icon: Flag },
    { title: 'Marca', href: '/platform/branding', icon: Palette },
];

function isActive(href: string): boolean {
    if (href === '/platform') {
        return currentUrl.value === '/platform';
    }

    return currentUrl.value.startsWith(href);
}
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border/60 bg-background/95">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Link href="/platform" class="flex items-center gap-2">
                        <span
                            class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                        >
                            <Shield class="size-5" />
                        </span>
                        <span>
                            <span class="block text-lg font-semibold">
                                Plataforma
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                Operación y moderación
                            </span>
                        </span>
                    </Link>

                    <div class="flex items-center gap-3 text-sm">
                        <span class="hidden text-muted-foreground sm:inline">
                            {{ page.props.auth.user?.name }}
                        </span>
                        <Link
                            :href="appUrl"
                            class="rounded-md border px-3 py-1.5 font-medium hover:bg-accent"
                        >
                            Volver a la app
                        </Link>
                    </div>
                </div>

                <nav class="flex flex-wrap items-center gap-1 text-sm">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-md px-3 py-2 transition-colors"
                        :class="
                            isActive(item.href)
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.title }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6">
            <slot />
        </main>
    </div>
</template>
