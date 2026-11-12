<script setup lang="ts">
import { Maximize2 } from '@lucide/vue';
import { computed, nextTick, ref, useId, watch } from 'vue';
import FeatureListItem from './FeatureListItem.vue';
import type { ProductFeatureTab } from './productFeatureTabs';

const props = withDefaults(
    defineProps<{
        tabs: ProductFeatureTab[];
        ariaLabel?: string;
        screenshotPlaceholder?: string;
    }>(),
    {
        ariaLabel: 'Funcionalidades de la plataforma',
        screenshotPlaceholder: 'Captura próximamente',
    },
);

const uid = useId();
const panelId = `product-tab-panel-${uid}`;
const tabId = (id: string): string => `product-tab-${uid}-${id}`;

const activeId = ref(props.tabs[0]?.id ?? '');
const failedScreenshots = ref<Record<string, boolean>>({});

const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function scrollTabIntoView(id: string, focus = false): void {
    nextTick(() => {
        const element = document.getElementById(tabId(id));

        if (!element) {
            return;
        }

        if (focus) {
            element.focus();
        }

        element.scrollIntoView({
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            block: 'nearest',
            inline: 'nearest',
        });
    });
}

const activeTab = computed(
    () =>
        props.tabs.find((tab) => tab.id === activeId.value) ??
        props.tabs[0] ??
        null,
);

const screenshot = computed(() => {
    const tab = activeTab.value;

    if (!tab?.screenshot || failedScreenshots.value[tab.id]) {
        return null;
    }

    return tab.screenshot;
});

watch(
    () => props.tabs,
    (tabs) => {
        if (!tabs.some((tab) => tab.id === activeId.value)) {
            activeId.value = tabs[0]?.id ?? '';
        }
    },
);

function select(id: string): void {
    activeId.value = id;
    scrollTabIntoView(id);
}

function onTabKeydown(event: KeyboardEvent, index: number): void {
    if (
        !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key) ||
        props.tabs.length === 0
    ) {
        return;
    }

    event.preventDefault();

    let next = index;

    if (event.key === 'ArrowRight') {
        next = (index + 1) % props.tabs.length;
    } else if (event.key === 'ArrowLeft') {
        next = (index - 1 + props.tabs.length) % props.tabs.length;
    } else if (event.key === 'Home') {
        next = 0;
    } else if (event.key === 'End') {
        next = props.tabs.length - 1;
    }

    const nextId = props.tabs[next].id;
    activeId.value = nextId;
    scrollTabIntoView(nextId, true);
}

function markScreenshotFailed(id: string): void {
    failedScreenshots.value = { ...failedScreenshots.value, [id]: true };
}
</script>

<template>
    <div class="w-full">
        <div
            class="product-tabs-nav flex gap-2 overflow-x-auto pb-1"
            role="tablist"
            :aria-label="ariaLabel"
        >
            <button
                v-for="(tab, index) in tabs"
                :id="tabId(tab.id)"
                :key="tab.id"
                type="button"
                role="tab"
                :aria-selected="tab.id === activeId"
                :aria-controls="panelId"
                :tabindex="tab.id === activeId ? 0 : -1"
                class="home-tab shrink-0"
                :class="{ 'home-tab--active': tab.id === activeId }"
                @click="select(tab.id)"
                @keydown="onTabKeydown($event, index)"
            >
                {{ tab.label }}
            </button>
        </div>

        <div
            :id="panelId"
            role="tabpanel"
            :aria-labelledby="activeTab ? tabId(activeTab.id) : undefined"
            tabindex="0"
            class="product-tabs-panel mt-6 rounded-[1.25rem] border border-[color:var(--home-border)] bg-[color:var(--home-surface)] p-5 shadow-[var(--home-shadow-card)] sm:p-6 lg:p-8"
        >
            <div class="grid">
                <Transition name="product-tab-panel">
                    <div
                        v-if="activeTab"
                        :key="activeTab.id"
                        class="col-start-1 row-start-1 grid gap-8 md:grid-cols-[35fr_65fr] md:items-start md:gap-8"
                    >
                        <div class="min-w-0">
                            <p class="home-eyebrow">{{ activeTab.eyebrow }}</p>
                            <h3
                                class="mt-2 text-2xl leading-tight font-extrabold sm:text-3xl"
                            >
                                {{ activeTab.title }}
                            </h3>
                            <p
                                class="mt-3 text-base leading-relaxed text-[color:var(--home-secondary)]"
                            >
                                {{ activeTab.description }}
                            </p>
                            <ul
                                class="mt-5 space-y-3 text-base text-[color:var(--home-secondary)]"
                            >
                                <li
                                    v-for="feature in activeTab.features"
                                    :key="feature.text"
                                >
                                    <FeatureListItem
                                        :icon="feature.icon ?? 'check'"
                                        :text="feature.text"
                                        variant="soft"
                                    />
                                </li>
                            </ul>
                        </div>

                        <div class="min-w-0">
                            <a
                                v-if="screenshot"
                                :href="screenshot"
                                target="_blank"
                                rel="noopener"
                                class="group relative block aspect-[16/10] overflow-hidden rounded-[var(--home-radius-card)] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] shadow-[var(--home-shadow-card)]"
                                :aria-label="`Ampliar captura de ${activeTab.eyebrow}`"
                            >
                                <img
                                    :src="screenshot"
                                    :alt="activeTab.screenshotAlt"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                    decoding="async"
                                    @error="markScreenshotFailed(activeTab.id)"
                                />
                                <span
                                    class="absolute right-3 bottom-3 inline-flex items-center gap-1.5 rounded-full bg-[color:var(--home-surface)]/90 px-3 py-1.5 text-xs font-semibold text-[color:var(--home-dark)] shadow-[var(--home-shadow-card)]"
                                >
                                    <Maximize2
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Ampliar captura
                                </span>
                            </a>

                            <div
                                v-else
                                class="flex aspect-[16/10] w-full flex-col items-center justify-center gap-3 rounded-[var(--home-radius-card)] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] px-6 text-center"
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
                                    {{ screenshotPlaceholder }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.product-tabs-nav {
    scrollbar-width: none;
}

.product-tabs-nav::-webkit-scrollbar {
    display: none;
}

.product-tab-panel-enter-active,
.product-tab-panel-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.product-tab-panel-enter-from,
.product-tab-panel-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

@media (prefers-reduced-motion: reduce) {
    .product-tab-panel-enter-active,
    .product-tab-panel-leave-active {
        transition: none;
    }
}

/* Keep the stacked mobile panel from resizing between tabs of similar length. */
@media (max-width: 639px) {
    .product-tabs-panel {
        min-height: 44rem;
    }
}
</style>
