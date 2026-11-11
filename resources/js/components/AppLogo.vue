<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();

const branding = computed(
    () =>
        (page.props.branding ?? {}) as {
            app_name?: string | null;
            logo_url?: string | null;
        },
);

const name = computed(() => branding.value.app_name || page.props.name);
const logoUrl = computed(() => branding.value.logo_url ?? null);
</script>

<template>
    <img
        v-if="logoUrl"
        :src="logoUrl"
        :alt="name"
        class="size-8 rounded-md object-contain"
    />
    <div
        v-else
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
