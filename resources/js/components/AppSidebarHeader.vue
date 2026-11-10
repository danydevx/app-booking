<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { BreadcrumbItem, SubmenuItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
        submenu?: SubmenuItem[];
    }>(),
    {
        breadcrumbs: () => [],
        submenu: () => [],
    },
);

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const effectiveSubmenu = computed<SubmenuItem[]>(() => {
    const shared = (
        page.props.listingAdmin as
            | { submenu?: SubmenuItem[] }
            | null
            | undefined
    )?.submenu;

    return shared && shared.length > 0 ? shared : props.submenu;
});
</script>

<template>
    <header
        class="sticky top-0 z-30 shrink-0 border-b border-sidebar-border/70 bg-background/95 backdrop-blur"
    >
        <div
            class="flex min-h-16 items-center gap-2 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12 md:px-4"
        >
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
            <div class="ml-auto flex items-center gap-2">
                <NotificationBell />
            </div>
        </div>

        <nav
            v-if="effectiveSubmenu.length > 0"
            class="flex flex-wrap items-center gap-1 border-t border-sidebar-border/50 px-6 py-1.5 md:px-4"
            aria-label="Secciones de administración"
        >
            <Link
                v-for="item in effectiveSubmenu"
                :key="item.title"
                :href="item.href"
                class="rounded-md px-3 py-1.5 text-sm transition-colors"
                :class="
                    isCurrentUrl(item.href)
                        ? 'bg-primary/10 font-medium text-primary'
                        : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                "
                :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
            >
                {{ item.title }}
            </Link>
        </nav>
    </header>
</template>
