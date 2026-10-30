<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
        aria-label="Paginación"
    >
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-background hover:bg-accent'
                "
                v-html="link.label"
            />
            <span
                v-else
                class="rounded-md border border-border/50 px-3 py-1.5 text-sm text-muted-foreground/50"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
