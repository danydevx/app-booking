<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem, SubmenuItem } from '@/types';

const { breadcrumbs = [], submenu = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    submenu?: SubmenuItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :submenu="submenu">
        <slot />
    </AppLayout>
</template>
