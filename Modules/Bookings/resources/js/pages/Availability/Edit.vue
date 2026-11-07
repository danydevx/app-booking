<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AvailabilityCalendar from '../../components/AvailabilityCalendar.vue';
import type { BlockedDate } from '../../types';

const props = defineProps<{
    listing: {
        id: number;
        name: string;
        slug: string;
        status: string;
        status_label: string;
        timezone: string;
    };
    blocked_dates: BlockedDate[];
    urls: {
        bookings: string;
        block_range: string;
        unblock: string;
        unblock_range: string;
        public_listing: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Solicitudes',
                href: '/',
            },
            {
                title: 'Disponibilidad',
            },
        ],
    },
});

const form = useForm({
    start_date: '',
    end_date: '',
    reason: '',
});

function submit(): void {
    form.post(props.urls.block_range, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const unblockForm = useForm({
    start_date: '',
    end_date: '',
});

function unblockRange(): void {
    unblockForm.delete(props.urls.unblock_range, {
        preserveScroll: true,
        onSuccess: () => unblockForm.reset(),
    });
}

function unblock(date: string): void {
    if (!window.confirm('¿Liberar esta fecha?')) {
        return;
    }

    router.delete(props.urls.unblock.replace('__DATE__', date), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Disponibilidad · ${listing.name}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ listing.name }}</h1>
                    <Badge variant="secondary">{{
                        listing.status_label
                    }}</Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Zona horaria: {{ listing.timezone }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.bookings">Solicitudes</Link>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="urls.public_listing"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver pública
                    </a>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <AvailabilityCalendar :blocked-dates="blocked_dates" :months="3" />

            <aside class="space-y-4">
                <form
                    class="rounded-xl border bg-card p-4"
                    @submit.prevent="submit"
                >
                    <h2 class="mb-3 font-semibold">Bloquear fechas</h2>
                    <div class="grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="start_date">Desde</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    required
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">Hasta</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    required
                                />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Se incluyen ambas fechas. Para un solo día, usa la
                            misma fecha en ambos campos.
                        </p>
                        <div class="grid gap-2">
                            <Label for="reason">Motivo</Label>
                            <Input
                                id="reason"
                                v-model="form.reason"
                                placeholder="Mantenimiento"
                            />
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            Bloquear rango
                        </Button>
                    </div>
                </form>

                <form
                    class="rounded-xl border bg-card p-4"
                    @submit.prevent="unblockRange"
                >
                    <h2 class="mb-3 font-semibold">Liberar rango</h2>
                    <div class="grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="unblock_start_date">Desde</Label>
                                <Input
                                    id="unblock_start_date"
                                    v-model="unblockForm.start_date"
                                    type="date"
                                    required
                                />
                                <InputError
                                    :message="unblockForm.errors.start_date"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="unblock_end_date">Hasta</Label>
                                <Input
                                    id="unblock_end_date"
                                    v-model="unblockForm.end_date"
                                    type="date"
                                    required
                                />
                                <InputError
                                    :message="unblockForm.errors.end_date"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Solo libera fechas bloqueadas manualmente. Las
                            reservadas o apartadas se conservan.
                        </p>
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="unblockForm.processing"
                        >
                            Liberar rango
                        </Button>
                    </div>
                </form>

                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-3 font-semibold">Fechas no disponibles</h2>
                    <div v-if="blocked_dates.length > 0" class="space-y-2">
                        <div
                            v-for="date in blocked_dates"
                            :key="date.date"
                            class="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                        >
                            <div>
                                <p class="font-medium">{{ date.date }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ date.label }}
                                    <span v-if="date.reason">
                                        · {{ date.reason }}</span
                                    >
                                </p>
                            </div>
                            <Button
                                v-if="date.status === 'blocked'"
                                size="sm"
                                variant="outline"
                                @click="unblock(date.date)"
                            >
                                Liberar
                            </Button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Todas las fechas están disponibles.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
