<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicShell from '@/components/PublicShell.vue';

defineProps<{
    customerName: string;
}>();
</script>

<template>
    <Head title="Suscripción cancelada" />

    <PublicShell>
        <div class="mx-auto max-w-xl rounded-xl border bg-card p-6 text-center">
            <h1 class="text-2xl font-semibold">Suscripción cancelada</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ customerName }}, ya no recibirás correos de seguimiento.
            </p>
            <p class="mt-4 text-sm text-muted-foreground">
                Si cambias de opinión, puedes pedirle al negocio que reactive el
                contacto por correo.
            </p>
        </div>
    </PublicShell>
</template>
