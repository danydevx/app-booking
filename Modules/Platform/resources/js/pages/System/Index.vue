<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type FailedJob = {
    uuid: string;
    queue: string;
    job: string;
    exception: string;
    failed_at: string;
    retry_url: string;
    delete_url: string;
};

const props = defineProps<{
    status: {
        environment: string;
        debug: boolean;
        php_version: string;
        laravel_version: string;
        queue_connection: string;
        mailer: string;
        stripe_configured: boolean;
        scheduler_last_run: string | null;
        failed_jobs: number;
        queued_jobs: number;
        database: boolean;
        cache: boolean;
        storage: boolean;
    };
    failedJobs: FailedJob[];
}>();

function formatDate(value: string | null): string {
    if (!value) {
        return 'Sin registro';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function retryJob(job: FailedJob): void {
    if (!window.confirm(`¿Reintentar ${job.job}?`)) {
        return;
    }

    router.post(job.retry_url, {}, { preserveScroll: true });
}

function forgetJob(job: FailedJob): void {
    if (!window.confirm(`¿Eliminar el trabajo fallido ${job.job}?`)) {
        return;
    }

    router.delete(job.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Sistema · Plataforma" />

    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-semibold">Estado del sistema</h1>
            <p class="text-sm text-muted-foreground">
                Cola, scheduler, almacenamiento y configuración operativa.
            </p>
        </div>

        <div
            v-if="status.debug || status.environment !== 'production'"
            class="rounded-xl border border-amber-500/40 bg-amber-500/5 p-4 text-sm"
        >
            <p class="font-medium">Entorno no productivo</p>
            <p class="text-muted-foreground">
                APP_ENV={{ status.environment }} y APP_DEBUG={{
                    status.debug ? 'true' : 'false'
                }}. Revisa el preflight antes de desplegar.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Base de datos</CardDescription>
                    <CardTitle>
                        <Badge
                            :variant="
                                status.database ? 'default' : 'destructive'
                            "
                        >
                            {{ status.database ? 'Conectada' : 'Sin conexión' }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    {{ status.queued_jobs }} trabajos en cola
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Caché</CardDescription>
                    <CardTitle>
                        <Badge
                            :variant="status.cache ? 'default' : 'destructive'"
                        >
                            {{ status.cache ? 'Operativa' : 'Con fallos' }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Scheduler: {{ formatDate(status.scheduler_last_run) }}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Almacenamiento</CardDescription>
                    <CardTitle>
                        <Badge
                            :variant="
                                status.storage ? 'default' : 'destructive'
                            "
                        >
                            {{ status.storage ? 'Escribible' : 'Sin permisos' }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    storage y bootstrap/cache
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardDescription>Trabajos fallidos</CardDescription>
                    <CardTitle class="text-3xl">
                        {{ status.failed_jobs }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Requieren revisión o reintento.
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Entorno</CardTitle>
                    <CardDescription>
                        Versiones y servicios configurados.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p>
                        <span class="text-muted-foreground">APP_ENV:</span>
                        {{ status.environment }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">PHP:</span>
                        {{ status.php_version }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">Laravel:</span>
                        {{ status.laravel_version }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Servicios</CardTitle>
                    <CardDescription>
                        Configuración activa de colas, correo y Stripe.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p>
                        <span class="text-muted-foreground">Cola:</span>
                        {{ status.queue_connection }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">Correo:</span>
                        {{ status.mailer }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">Stripe:</span>
                        {{
                            status.stripe_configured
                                ? 'Configurado'
                                : 'No configurado'
                        }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Trabajos fallidos recientes</CardTitle>
                <CardDescription>
                    Últimos diez errores de la cola. Reintentar vuelve a
                    encolarlos; eliminar los descarta.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="failedJobs.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-muted-foreground uppercase">
                            <tr>
                                <th class="py-2 pr-4">Trabajo</th>
                                <th class="py-2 pr-4">Cola</th>
                                <th class="py-2 pr-4">Fecha</th>
                                <th class="py-2 pr-4">Error</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="job in failedJobs" :key="job.uuid">
                                <td class="py-3 pr-4 font-medium">
                                    {{ job.job }}
                                </td>
                                <td class="py-3 pr-4">{{ job.queue }}</td>
                                <td
                                    class="py-3 pr-4 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDate(job.failed_at) }}
                                </td>
                                <td
                                    class="max-w-md py-3 pr-4 text-xs text-muted-foreground"
                                >
                                    {{ job.exception }}
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="retryJob(job)"
                                        >
                                            Reintentar
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="forgetJob(job)"
                                        >
                                            Eliminar
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No hay trabajos fallidos.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
