<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Check } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type NotificationItem = {
    id: string;
    title: string;
    message: string;
    url: string | null;
    kind: string;
    read_at: string | null;
    created_at: string | null;
};

const page = usePage();
const shared = computed(
    () =>
        (page.props.notifications as { unread_count: number } | undefined) ?? {
            unread_count: 0,
        },
);

const unread = ref(shared.value.unread_count);
const items = ref<NotificationItem[]>([]);
const loading = ref(false);
let timer: ReturnType<typeof setInterval> | null = null;

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function fetchNotifications(): Promise<void> {
    loading.value = true;

    try {
        const response = await fetch('/app/notifications', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            notifications: NotificationItem[];
            unread_count: number;
        };

        items.value = data.notifications;
        unread.value = data.unread_count;
    } finally {
        loading.value = false;
    }
}

async function markRead(item: NotificationItem): Promise<void> {
    if (item.read_at !== null) {
        return;
    }

    const response = await fetch(`/app/notifications/${item.id}/read`, {
        method: 'PATCH',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (response.ok) {
        item.read_at = new Date().toISOString();
        unread.value = Math.max(0, unread.value - 1);
    }
}

async function markAll(): Promise<void> {
    const response = await fetch('/app/notifications/read-all', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (response.ok) {
        items.value = items.value.map((item) => ({
            ...item,
            read_at: item.read_at ?? new Date().toISOString(),
        }));
        unread.value = 0;
    }
}

async function openNotification(item: NotificationItem): Promise<void> {
    await markRead(item);

    if (item.url) {
        router.visit(item.url);
    }
}

function handleOpen(value: boolean): void {
    if (value) {
        void fetchNotifications();
    }
}

function formatDate(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

onMounted(() => {
    void fetchNotifications();
    timer = setInterval(fetchNotifications, 60000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <DropdownMenu @update:open="handleOpen">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                aria-label="Notificaciones"
            >
                <Bell class="size-5" />
                <span
                    v-if="unread > 0"
                    class="absolute -top-1 -right-1 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                >
                    {{ unread > 9 ? '9+' : unread }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b px-3 py-2">
                <span class="text-sm font-medium">Notificaciones</span>
                <Button
                    v-if="unread > 0"
                    size="sm"
                    variant="ghost"
                    class="h-7"
                    @click="markAll"
                >
                    <Check class="size-3.5" />
                    Marcar leídas
                </Button>
            </div>

            <div
                v-if="items.length === 0"
                class="p-4 text-sm text-muted-foreground"
            >
                {{ loading ? 'Cargando…' : 'Sin notificaciones.' }}
            </div>

            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="block w-full border-b px-3 py-2 text-left transition-colors last:border-0 hover:bg-accent"
                @click="openNotification(item)"
            >
                <div class="flex items-start justify-between gap-2">
                    <span class="text-sm font-medium">{{ item.title }}</span>
                    <span
                        v-if="!item.read_at"
                        class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                    />
                </div>
                <p class="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                    {{ item.message }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    {{ formatDate(item.created_at) }}
                </p>
            </button>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
