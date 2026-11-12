<script setup lang="ts">
import { ref, watch } from 'vue';
import type { VideoShowcaseTrack } from './videoShowcase';

const props = defineProps<{
    title: string;
    description: string;
    src: string | null;
    poster: string | null;
    fallback: string;
    unavailable: string;
    tracks?: VideoShowcaseTrack[];
}>();

const failed = ref(false);

watch(
    () => props.src,
    () => {
        failed.value = false;
    },
);

function handleError(): void {
    failed.value = true;
}
</script>

<template>
    <section
        class="border-t border-[color:var(--home-border)] bg-[color:var(--home-surface)]"
    >
        <div class="home-container py-16 sm:py-24">
            <div class="max-w-3xl">
                <h2
                    class="text-[2rem] leading-tight font-extrabold sm:text-[2.5rem]"
                >
                    {{ title }}
                </h2>
                <p
                    class="mt-4 text-lg leading-relaxed text-[color:var(--home-secondary)]"
                >
                    {{ description }}
                </p>
            </div>

            <div class="mt-10 sm:mt-12">
                <div
                    class="home-media aspect-video overflow-hidden rounded-[var(--home-radius-card)] border border-[color:var(--home-border)] shadow-[var(--home-shadow-card)]"
                >
                    <video
                        v-if="src && !failed"
                        :src="src"
                        :poster="poster ?? undefined"
                        :aria-label="title"
                        class="h-full w-full object-cover"
                        controls
                        playsinline
                        preload="metadata"
                        @error="handleError"
                    >
                        <track
                            v-for="track in tracks"
                            :key="track.src"
                            :src="track.src"
                            :label="track.label"
                            :srclang="track.lang"
                            :kind="track.kind ?? 'subtitles'"
                            :default="track.default ?? false"
                        />
                        {{ fallback }}
                    </video>

                    <div
                        v-else
                        class="flex h-full w-full flex-col items-center justify-center gap-3 px-6 text-center"
                    >
                        <svg
                            width="32"
                            height="32"
                            viewBox="0 0 24 24"
                            fill="none"
                            class="text-[color:var(--home-muted)]"
                            aria-hidden="true"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                                stroke="currentColor"
                                stroke-width="1.5"
                            />
                            <path
                                d="M10 9.5v5l4.5-2.5L10 9.5z"
                                fill="currentColor"
                            />
                        </svg>
                        <p
                            class="text-sm font-semibold text-[color:var(--home-secondary)]"
                        >
                            {{ unavailable }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
