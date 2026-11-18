<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PageSeo } from '@/types';

const props = defineProps<{
    seo: PageSeo;
    image?: string | null;
    ogTitle?: string;
    ogDescription?: string;
}>();

const page = usePage();

const siteName = computed(
    () =>
        props.seo.site_name ||
        (page.props.branding as { app_name?: string } | undefined)?.app_name ||
        'App Booking',
);

const description = computed(() => props.seo.description ?? null);
const image = computed(() => props.image ?? props.seo.image ?? null);
const canonical = computed(() => props.seo.canonical ?? null);
const ogTitle = computed(() => props.ogTitle ?? props.seo.title);
const ogDescription = computed(() => props.ogDescription ?? description.value);
</script>

<template>
    <Head :title="seo.title">
        <meta v-if="description" name="description" :content="description" />
        <meta name="robots" :content="seo.robots" />
        <link v-if="canonical" rel="canonical" :href="canonical" />

        <meta property="og:type" :content="seo.type" />
        <meta property="og:site_name" :content="siteName" />
        <meta property="og:title" :content="ogTitle" />
        <meta
            v-if="ogDescription"
            property="og:description"
            :content="ogDescription"
        />
        <meta v-if="canonical" property="og:url" :content="canonical" />
        <meta v-if="image" property="og:image" :content="image" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="ogTitle" />
        <meta
            v-if="ogDescription"
            name="twitter:description"
            :content="ogDescription"
        />
        <meta v-if="image" name="twitter:image" :content="image" />
    </Head>
</template>
