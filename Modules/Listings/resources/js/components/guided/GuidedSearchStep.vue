<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string | null;
}>();
</script>

<template>
    <section aria-labelledby="guided-step-title" class="mx-auto max-w-4xl">
        <h2
            id="guided-step-title"
            class="text-center text-3xl leading-tight font-extrabold text-[color:var(--home-dark)] sm:text-4xl"
        >
            {{ title }}
        </h2>
        <p
            v-if="subtitle"
            class="mx-auto mt-3 max-w-2xl text-center text-base leading-relaxed text-[color:var(--home-secondary)]"
        >
            {{ subtitle }}
        </p>

        <div class="mt-8">
            <slot />
        </div>
    </section>
</template>
