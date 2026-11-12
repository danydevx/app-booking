<script setup lang="ts">
import { A11y, Autoplay, Pagination } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import { nextTick, ref } from 'vue';
import type { HeroSlide } from './heroSlider';

withDefaults(
    defineProps<{
        slides: HeroSlide[];
        ariaLabel?: string;
    }>(),
    {
        ariaLabel: 'Espacios para eventos',
    },
);

const modules = [A11y, Autoplay, Pagination];

const paginationEl = ref<HTMLElement | null>(null);

const reducedMotion = ref(
    typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches,
);

const autoplay = {
    delay: 7000,
    disableOnInteraction: true,
    pauseOnMouseEnter: true,
};

const a11y = {
    enabled: true,
    paginationBulletMessage: 'Ir a la imagen {{index}}',
    slideLabelMessage: '{{index}} de {{slidesLength}}',
};

function onSwiper(instance: SwiperInstance): void {
    nextTick(() => {
        if (!paginationEl.value) {
            return;
        }

        const pagination = instance.params.pagination;

        if (typeof pagination !== 'object' || pagination === null) {
            return;
        }

        pagination.el = paginationEl.value;
        instance.pagination.init();
        instance.pagination.render();
        instance.pagination.update();
    });
}
</script>

<template>
    <div class="w-full min-w-0">
        <div class="overflow-hidden rounded-[1.25rem]">
            <Swiper
                :modules="modules"
                effect="slide"
                :slides-per-view="1"
                :space-between="0"
                :speed="reducedMotion ? 0 : 700"
                :loop="true"
                :allow-touch-move="true"
                :watch-overflow="false"
                :autoplay="reducedMotion ? false : autoplay"
                :pagination="{ clickable: true }"
                :a11y="a11y"
                role="region"
                aria-roledescription="carrusel"
                :aria-label="ariaLabel"
                class="hero-showcase-swiper aspect-[4/3]"
                @swiper="onSwiper"
            >
                <SwiperSlide v-for="(slide, index) in slides" :key="slide.src">
                    <img
                        :src="slide.src"
                        :alt="slide.alt"
                        class="h-full w-full object-cover"
                        :loading="index === 0 ? 'eager' : 'lazy'"
                        :fetchpriority="index === 0 ? 'high' : undefined"
                        decoding="async"
                    />
                </SwiperSlide>
            </Swiper>
        </div>

        <div
            ref="paginationEl"
            class="hero-showcase-pagination mx-auto mt-4 flex min-h-[21px] w-fit items-center justify-center rounded-full border border-[color:var(--home-border)] bg-[color:var(--home-surface)]/90 px-3 py-1.5 shadow-[var(--home-shadow-card)]"
        ></div>
    </div>
</template>

<style scoped>
.hero-showcase-pagination :deep(.swiper-pagination-bullet) {
    display: inline-block;
    width: 7px;
    height: 7px;
    margin: 0 5px;
    border-radius: 9999px;
    background: var(--home-muted);
    opacity: 0.45;
    cursor: pointer;
    transition:
        opacity 200ms ease,
        background-color 200ms ease;
}

.hero-showcase-pagination :deep(.swiper-pagination-bullet-active) {
    background: var(--home-primary);
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .hero-showcase-pagination :deep(.swiper-pagination-bullet) {
        transition: none;
    }
}
</style>
