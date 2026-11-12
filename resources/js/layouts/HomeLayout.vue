<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HomeFooter from '@/components/home/HomeFooter.vue';
import HomeHeader from '@/components/home/HomeHeader.vue';

// Only this layout loads `resources/css/home.css` (via the page entry), so the
// catalog, the rest of the public site and the admin keep their own styles.
</script>

<template>
    <div class="home-site flex min-h-screen flex-col">
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

        <HomeHeader />

        <main class="flex-1">
            <slot />
        </main>

        <HomeFooter />
    </div>
</template>
