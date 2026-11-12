<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HomeFooter from '@/components/home/HomeFooter.vue';
import HomeHeader from '@/components/home/HomeHeader.vue';

// Full-height explore layout: the header and search bar are fixed rows and the
// map/list area fills the viewport. The site footer follows at full width below.
</script>

<template>
    <div class="home-site flex min-h-[100dvh] flex-col">
        <Head>
            <link
                head-key="home-font-preconnect"
                rel="preconnect"
                href="https://fonts.bunny.net"
                crossorigin="anonymous"
            />
            <link
                head-key="home-fonts"
                rel="stylesheet"
                href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap"
            />
        </Head>

        <div class="shrink-0">
            <HomeHeader wide />
        </div>

        <main class="min-h-0">
            <slot />
        </main>

        <HomeFooter flush />
    </div>
</template>
