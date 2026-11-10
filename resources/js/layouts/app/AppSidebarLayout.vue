<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem, SubmenuItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    submenu?: SubmenuItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    submenu: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" :submenu="submenu" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
