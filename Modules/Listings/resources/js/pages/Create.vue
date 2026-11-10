<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ListingForm from '../components/ListingForm.vue';
import type {
    ListingDetailField,
    ListingLimits,
    ServiceOption,
} from '../types';

defineProps<{
    services: ServiceOption[];
    detailFields: Record<string, ListingDetailField>;
    limits: ListingLimits;
    urls: {
        index: string;
        store: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Terrazas',
                href: '/',
            },
            {
                title: 'Nueva terraza',
            },
        ],
    },
});
</script>

<template>
    <Head title="Nueva terraza" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Nueva terraza</h1>
            <p class="text-sm text-muted-foreground">
                Guarda la información básica primero. Después podrás agregar
                fotos y publicarla.
            </p>
        </div>

        <div class="rounded-xl border bg-card p-5 sm:p-6">
            <ListingForm
                mode="create"
                :services="services"
                :detail-fields="detailFields"
                :urls="urls"
            />
        </div>
    </div>
</template>
