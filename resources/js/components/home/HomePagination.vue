<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1.5 py-6"
        aria-label="Paginación del catálogo"
    >
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="inline-flex min-w-9 items-center justify-center rounded-[0.625rem] border px-3 py-2 text-sm font-semibold transition-colors"
                :class="
                    link.active
                        ? 'border-[color:var(--home-primary)] bg-[color:var(--home-primary)] text-white'
                        : 'border-[color:var(--home-border)] bg-white text-[color:var(--home-dark)] hover:border-[color:var(--home-primary)]'
                "
                v-html="link.label"
            />
            <span
                v-else
                class="inline-flex min-w-9 items-center justify-center rounded-[0.625rem] border border-[color:var(--home-border)] px-3 py-2 text-sm text-[color:var(--home-muted)]"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
