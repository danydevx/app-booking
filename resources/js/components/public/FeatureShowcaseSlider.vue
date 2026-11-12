<script setup lang="ts">
import { Keyboard } from 'swiper/modules';
import type { Swiper as SwiperInstance } from 'swiper';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import { computed, onMounted, ref } from 'vue';
import FeatureIcon from './FeatureIcon.vue';
import type { FeatureSlide } from './featureShowcase';

const props = withDefaults(
    defineProps<{
        slides: FeatureSlide[];
        ariaLabel?: string;
    }>(),
    {
        ariaLabel: 'Funcionalidades del producto',
    },
);

const modules = [Keyboard];

const swiper = ref<SwiperInstance | null>(null);
const activeIndex = ref(0);
const isBeginning = ref(true);
const isEnd = ref(false);
const reducedMotion = ref(false);
const failedImages = ref<Record<string, boolean>>({});

const total = computed(() => props.slides.length);

const progress = computed(() =>
    total.value === 0 ? 0 : ((activeIndex.value + 1) / total.value) * 100,
);

function onSwiper(instance: SwiperInstance): void {
    swiper.value = instance;
    syncState();
}

function syncState(): void {
    if (!swiper.value) {
        return;
    }

    activeIndex.value = swiper.value.realIndex;
    isBeginning.value = swiper.value.isBeginning;
    isEnd.value = swiper.value.isEnd;
}

function previous(): void {
    swiper.value?.slidePrev();
}

function next(): void {
    swiper.value?.slideNext();
}

function hasImage(slide: FeatureSlide): boolean {
    return Boolean(slide.image) && !failedImages.value[slide.id];
}

function markImageFailed(slide: FeatureSlide): void {
    failedImages.value = { ...failedImages.value, [slide.id]: true };
}

onMounted(() => {
    reducedMotion.value = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
});
</script>

<template>
    <div class="w-full">
        <Swiper
            :modules="modules"
            :slides-per-view="1"
            :space-between="0"
            :speed="reducedMotion ? 0 : 400"
            :loop="false"
            :watch-overflow="true"
            :keyboard="{ enabled: true, onlyInViewport: true }"
            tabindex="0"
            role="region"
            aria-roledescription="carrusel"
            :aria-label="ariaLabel"
            class="feature-showcase-swiper"
            @swiper="onSwiper"
            @slide-change="syncState"
            @reach-beginning="syncState"
            @reach-end="syncState"
        >
            <SwiperSlide
                v-for="(slide, index) in slides"
                :key="slide.id"
                role="group"
                aria-roledescription="slide"
                :aria-label="`${index + 1} de ${slides.length}`"
                :aria-hidden="index !== activeIndex ? 'true' : undefined"
                :inert="index !== activeIndex"
            >
                <div
                    class="grid gap-8 lg:grid-cols-[32fr_68fr] lg:items-center lg:gap-10"
                >
                    <div
                        class="feature-showcase-copy flex min-w-0 flex-col justify-center"
                    >
                        <div
                            v-if="slide.eyebrow || slide.icon"
                            class="flex items-center gap-3"
                        >
                            <FeatureIcon
                                v-if="slide.icon"
                                :name="slide.icon"
                                size="md"
                                variant="primary"
                            />
                            <p v-if="slide.eyebrow" class="home-eyebrow">
                                {{ slide.eyebrow }}
                            </p>
                        </div>
                        <h3
                            class="mt-2 text-2xl leading-tight font-extrabold sm:text-3xl"
                        >
                            {{ slide.title }}
                        </h3>
                        <p
                            v-if="slide.description"
                            class="mt-3 text-base leading-relaxed text-[color:var(--home-secondary)]"
                        >
                            {{ slide.description }}
                        </p>
                        <ul class="mt-5 space-y-2.5">
                            <li
                                v-for="(
                                    feature, featureIndex
                                ) in slide.features"
                                :key="feature"
                                class="feature-showcase-item flex gap-2.5 text-base leading-relaxed text-[color:var(--home-secondary)]"
                                :style="{ '--feature-index': featureIndex }"
                            >
                                <span
                                    class="mt-2 size-1.5 shrink-0 rounded-full bg-[color:var(--home-primary)]"
                                    aria-hidden="true"
                                ></span>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="feature-showcase-media flex aspect-[16/10] min-w-0 items-center justify-center overflow-hidden rounded-[1.125rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)]"
                    >
                        <img
                            v-if="hasImage(slide)"
                            :src="slide.image ?? ''"
                            :alt="slide.imageAlt ?? slide.title"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                            :fetchpriority="index === 0 ? 'high' : undefined"
                            decoding="async"
                            class="h-full w-full object-contain"
                            @error="markImageFailed(slide)"
                        />
                        <div
                            v-else
                            class="flex flex-col items-center gap-3 px-6 text-center"
                        >
                            <svg
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                class="text-[color:var(--home-muted)]"
                                aria-hidden="true"
                            >
                                <rect
                                    x="3"
                                    y="4"
                                    width="18"
                                    height="16"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M3 16l5-5 4 4 3-3 6 6"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    cx="16.5"
                                    cy="8.5"
                                    r="1.5"
                                    fill="currentColor"
                                />
                            </svg>
                            <p
                                class="text-sm font-semibold text-[color:var(--home-secondary)]"
                            >
                                Captura próximamente
                            </p>
                        </div>
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>

        <div class="mt-6 grid gap-6 lg:grid-cols-[32fr_68fr] lg:gap-10">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex size-11 items-center justify-center rounded-full border border-[color:var(--home-border)] bg-white text-[color:var(--home-dark)] transition-colors hover:border-[color:var(--home-primary)] hover:text-[color:var(--home-primary-deep)] disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Slide anterior"
                    :disabled="isBeginning"
                    @click="previous"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M12.5 4.5L7 10l5.5 5.5"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    class="flex size-11 items-center justify-center rounded-full border border-[color:var(--home-border)] bg-white text-[color:var(--home-dark)] transition-colors hover:border-[color:var(--home-primary)] hover:text-[color:var(--home-primary-deep)] disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Slide siguiente"
                    :disabled="isEnd"
                    @click="next"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M7.5 4.5L13 10l-5.5 5.5"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>

                <div class="ml-1 flex min-w-0 flex-1 items-center gap-3">
                    <div
                        class="h-1 min-w-0 flex-1 overflow-hidden rounded-full bg-[color:var(--home-border)]"
                        role="progressbar"
                        :aria-label="`Slide ${activeIndex + 1} de ${total}`"
                        :aria-valuemin="1"
                        :aria-valuemax="total"
                        :aria-valuenow="activeIndex + 1"
                    >
                        <div
                            class="feature-showcase-progress h-full rounded-full bg-[color:var(--home-primary)] transition-[width] duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="shrink-0 text-sm font-semibold text-[color:var(--home-muted)] tabular-nums"
                    >
                        {{ String(activeIndex + 1).padStart(2, '0') }} /
                        {{ String(total).padStart(2, '0') }}
                    </span>
                    <span class="sr-only" aria-live="polite">
                        Slide {{ activeIndex + 1 }} de {{ total }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes feature-showcase-copy-in {
    from {
        opacity: 0.45;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes feature-showcase-media-in {
    from {
        opacity: 0.45;
        transform: scale(0.985);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes feature-showcase-item-in {
    from {
        opacity: 0.55;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feature-showcase-swiper :deep(.swiper-slide-active) .feature-showcase-copy {
    animation: feature-showcase-copy-in 420ms cubic-bezier(0.22, 0.61, 0.36, 1)
        both;
}

.feature-showcase-swiper :deep(.swiper-slide-active) .feature-showcase-media {
    animation: feature-showcase-media-in 480ms cubic-bezier(0.22, 0.61, 0.36, 1)
        50ms both;
}

.feature-showcase-swiper :deep(.swiper-slide-active) .feature-showcase-item {
    animation: feature-showcase-item-in 380ms cubic-bezier(0.22, 0.61, 0.36, 1)
        both;
    animation-delay: calc(40ms + var(--feature-index, 0) * 45ms);
}

@media (prefers-reduced-motion: reduce) {
    .feature-showcase-swiper :deep(.swiper-slide-active) .feature-showcase-copy,
    .feature-showcase-swiper
        :deep(.swiper-slide-active)
        .feature-showcase-media,
    .feature-showcase-swiper
        :deep(.swiper-slide-active)
        .feature-showcase-item {
        animation: none;
    }

    .feature-showcase-progress {
        transition: none;
    }
}
</style>
