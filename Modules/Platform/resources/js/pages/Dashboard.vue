<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    stats: {
        accounts: number;
        users: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Plataforma',
                href: '/platform',
            },
        ],
    },
});
</script>

<template>
    <Head title="Plataforma" />

    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Plataforma</h1>
            <p class="text-sm text-muted-foreground">
                Revisión operativa de cuentas y suscripciones.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Cuentas</CardTitle>
                    <CardDescription>Cuentas registradas.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">{{ stats.accounts }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Usuarios</CardTitle>
                    <CardDescription>Usuarios registrados.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">{{ stats.users }}</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
