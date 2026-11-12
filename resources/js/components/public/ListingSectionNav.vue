<script setup lang="ts">
import {
    Calendar,
    FileText,
    MapPin,
    Package,
    Sparkles,
    Tag,
    Wrench,
} from '@lucide/vue';
import type { Component } from 'vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

type SectionNavItem = {
    id: string;
    label: string;
    icon?: string | null;
    visible: boolean;
    targetId: string;
};

const props = withDefaults(
    defineProps<{
        sections: SectionNavItem[];
        ariaLabel?: string;
    }>(),
    {
        ariaLabel: 'Secciones de la terraza',
    },
);

const root = ref<HTMLElement | null>(null);
const list = ref<HTMLElement | null>(null);
const activeId = ref<string | null>(null);

const iconMap: Record<string, Component> = {
    calendar: Calendar,
    'file-text': FileText,
    'map-pin': MapPin,
    package: Package,
    sparkles: Sparkles,
    tag: Tag,
    wrench: Wrench,
};

const visibleSections = computed(() =>
    props.sections.filter((section) => section.visible),
);

let observer: IntersectionObserver | null = null;
let refreshFrame: number | null = null;
let stickyOffsetPx = 136;

function refreshActive(): void {
    let current = visibleSections.value[0]?.targetId ?? null;

    for (const section of visibleSections.value) {
        const target = document.getElementById(section.targetId);

        if (!target) {
            continue;
        }

        if (target.getBoundingClientRect().top <= stickyOffsetPx + 8) {
            current = section.targetId;
        } else {
            break;
        }
    }

    setActive(current);
}

function scheduleRefresh(): void {
    if (refreshFrame !== null) {
        return;
    }

    refreshFrame = window.requestAnimationFrame(() => {
        refreshFrame = null;
        refreshActive();
    });
}

function handleResize(): void {
    stickyOffsetPx = stickyOffset();
    scheduleRefresh();
}

function iconFor(icon?: string | null): Component | null {
    if (!icon) {
        return null;
    }

    return iconMap[icon] ?? null;
}

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function stickyOffset(): number {
    if (typeof window === 'undefined' || !root.value) {
        return 140;
    }

    const styles = window.getComputedStyle(root.value);
    const rootFontSize =
        Number.parseFloat(
            window.getComputedStyle(document.documentElement).fontSize,
        ) || 16;

    const header = cssLengthToPx(
        styles.getPropertyValue('--home-header-height'),
        68,
        rootFontSize,
    );
    const nav = cssLengthToPx(
        styles.getPropertyValue('--home-section-nav-height'),
        52,
        rootFontSize,
    );

    return header + nav + 16;
}

function cssLengthToPx(
    value: string,
    fallback: number,
    rootFontSize: number,
): number {
    const normalized = value.trim();
    const parsed = Number.parseFloat(normalized);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return normalized.endsWith('rem') ? parsed * rootFontSize : parsed;
}

function setActive(id: string | null): void {
    activeId.value = id;
}

function navigate(event: MouseEvent, section: SectionNavItem): void {
    const target = document.getElementById(section.targetId);

    if (!target) {
        return;
    }

    event.preventDefault();
    target.scrollIntoView({
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        block: 'start',
    });
    setActive(section.targetId);

    if (window.location.hash !== `#${section.targetId}`) {
        window.history.pushState(null, '', `#${section.targetId}`);
    }
}

function handleHash(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const id = decodeURIComponent(window.location.hash.replace(/^#/, ''));

    if (
        id === '' ||
        !visibleSections.value.some((section) => section.targetId === id)
    ) {
        return;
    }

    const target = document.getElementById(id);

    if (!target) {
        return;
    }

    setActive(id);
    target.scrollIntoView({
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        block: 'start',
    });
}

function setupObserver(): void {
    observer?.disconnect();
    observer = null;

    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
        return;
    }

    const offset = stickyOffsetPx;

    observer = new IntersectionObserver(
        () => {
            refreshActive();
        },
        {
            rootMargin: `-${offset}px 0px -55% 0px`,
            threshold: [0, 0.1, 0.5, 1],
        },
    );

    visibleSections.value.forEach((section) => {
        const target = document.getElementById(section.targetId);

        if (target) {
            observer?.observe(target);
        }
    });
}

function scrollActiveIntoView(): void {
    const container = list.value;
    const id = activeId.value;

    if (!container || id === null) {
        return;
    }

    const link = container.querySelector<HTMLElement>(
        `[data-section-id="${id}"]`,
    );

    if (!link) {
        return;
    }

    const left =
        container.scrollLeft +
        (link.getBoundingClientRect().left -
            container.getBoundingClientRect().left) -
        (container.clientWidth - link.clientWidth) / 2;

    container.scrollTo({
        left: Math.max(0, left),
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    });
}

watch(activeId, () => {
    void nextTick(scrollActiveIntoView);
});

watch(
    visibleSections,
    () => {
        void nextTick(() => {
            if (
                activeId.value === null ||
                !visibleSections.value.some(
                    (section) => section.targetId === activeId.value,
                )
            ) {
                setActive(visibleSections.value[0]?.targetId ?? null);
            }

            setupObserver();
        });
    },
    { deep: true },
);

onMounted(() => {
    void nextTick(() => {
        stickyOffsetPx = stickyOffset();
        setActive(visibleSections.value[0]?.targetId ?? null);

        if (window.location.hash !== '') {
            handleHash();
        }

        setupObserver();
        refreshActive();
    });

    window.addEventListener('hashchange', handleHash);
    window.addEventListener('scroll', scheduleRefresh, { passive: true });
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    window.removeEventListener('hashchange', handleHash);
    window.removeEventListener('scroll', scheduleRefresh);
    window.removeEventListener('resize', handleResize);

    if (refreshFrame !== null) {
        window.cancelAnimationFrame(refreshFrame);
    }
});
</script>

<template>
    <nav
        v-if="visibleSections.length > 0"
        ref="root"
        class="listing-section-nav"
        :aria-label="ariaLabel"
    >
        <div ref="list" class="listing-section-nav-scroll">
            <a
                v-for="section in visibleSections"
                :key="section.id"
                :href="`#${section.targetId}`"
                :data-section-id="section.targetId"
                :aria-current="
                    activeId === section.targetId ? 'location' : undefined
                "
                class="listing-section-link"
                @click="navigate($event, section)"
            >
                <component
                    :is="iconFor(section.icon)"
                    v-if="iconFor(section.icon)"
                    class="size-3.5 shrink-0"
                    aria-hidden="true"
                />
                <span>{{ section.label }}</span>
            </a>
        </div>
    </nav>
</template>
