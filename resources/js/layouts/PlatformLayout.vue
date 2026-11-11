<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    Flag,
    History,
    Layers,
    LayoutDashboard,
    Palette,
    Server,
    Shield,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { dashboard } from '@/routes';

const page = usePage();

const currentUrl = computed(
    () => new URL(page.url, 'http://localhost').pathname,
);

const appUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const branding = computed(
    () =>
        (page.props.branding ?? {}) as {
            app_name?: string | null;
            logo_url?: string | null;
        },
);

const appName = computed(() => branding.value.app_name || 'Plataforma');
const logoUrl = computed(() => branding.value.logo_url ?? null);

const navigation = [
    { title: 'Resumen', href: '/app/platform', icon: LayoutDashboard },
    { title: 'Cuentas', href: '/app/platform/accounts', icon: Users },
    { title: 'Usuarios', href: '/app/platform/users', icon: UserCog },
    { title: 'Terrazas', href: '/app/platform/listings', icon: Building2 },
    { title: 'Planes', href: '/app/platform/plans', icon: Layers },
    {
        title: 'Suscripciones',
        href: '/app/platform/subscriptions',
        icon: CreditCard,
    },
    { title: 'Reportes', href: '/app/platform/reports', icon: Flag },
    { title: 'Auditoría', href: '/app/platform/activity', icon: History },
    { title: 'Sistema', href: '/app/platform/system', icon: Server },
    { title: 'Marca', href: '/app/platform/branding', icon: Palette },
];

function isActive(href: string): boolean {
    if (href === '/app/platform') {
        return currentUrl.value === '/app/platform';
    }

    return currentUrl.value.startsWith(href);
}
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-40 border-b border-border/60 bg-background/95 backdrop-blur"
        >
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Link href="/app/platform" class="flex items-center gap-2">
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="appName"
                            class="size-9 rounded-lg object-contain"
                        />
                        <span
                            v-else
                            class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                        >
                            <Shield class="size-5" />
                        </span>
                        <span>
                            <span class="block text-lg font-semibold">
                                {{ appName }}
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                Operación y moderación
                            </span>
                        </span>
                    </Link>

                    <div class="flex items-center gap-2 text-sm">
                        <span class="hidden text-muted-foreground sm:inline">
                            {{ page.props.auth.user?.name }}
                        </span>
                        <ThemeToggle />
                        <Link
                            :href="appUrl"
                            class="rounded-md border px-3 py-1.5 font-medium hover:bg-accent"
                        >
                            Volver a la app
                        </Link>
                    </div>
                </div>

                <nav
                    class="-mx-1 flex items-center gap-1 overflow-x-auto px-1 pb-1 text-sm"
                    aria-label="Navegación de plataforma"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="flex shrink-0 items-center gap-2 rounded-md px-3 py-2 transition-colors"
                        :class="
                            isActive(item.href)
                                ? 'bg-primary/10 font-medium text-primary'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                        "
                        :aria-current="isActive(item.href) ? 'page' : undefined"
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
