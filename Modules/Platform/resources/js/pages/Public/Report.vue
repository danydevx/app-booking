<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ShieldAlert } from '@lucide/vue';
import PublicShell from '@/components/PublicShell.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    listing: {
        name: string;
        slug: string;
        city: string | null;
        state: string | null;
        cover_url: string | null;
    };
    reasons: Array<{ value: string; label: string }>;
    urls: {
        store: string;
        listing: string;
        index: string;
    };
}>();

const form = useForm({
    reason: '',
    message: '',
    reporter_email: '',
});

function submit(): void {
    form.post(props.urls.store, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Reportar · ${listing.name}`" />

    <PublicShell>
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="space-y-2">
                <Link
                    :href="urls.listing"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Volver a la terraza
                </Link>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Reportar terraza
                </h1>
                <p class="text-muted-foreground">
                    Ayúdanos a revisar {{ listing.name }}. El equipo de
                    plataforma revisará el reporte y no compartiremos tu correo
                    con el anfitrión.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Detalles del reporte</CardTitle>
                    <CardDescription>
                        {{
                            [listing.city, listing.state]
                                .filter(Boolean)
                                .join(', ') || 'Ubicación por confirmar'
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-1">
                            <label
                                for="reason"
                                class="text-sm font-medium"
                            >
                                Motivo
                            </label>
                            <select
                                id="reason"
                                v-model="form.reason"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecciona un motivo</option>
                                <option
                                    v-for="reason in reasons"
                                    :key="reason.value"
                                    :value="reason.value"
                                >
                                    {{ reason.label }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.reason"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.reason }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label
                                for="message"
                                class="text-sm font-medium"
                            >
                                Mensaje
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Describe lo que ocurrió (obligatorio para «Otro motivo»)."
                            />
                            <p
                                v-if="form.errors.message"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label
                                for="reporter_email"
                                class="text-sm font-medium"
                            >
                                Tu correo (opcional)
                            </label>
                            <Input
                                id="reporter_email"
                                v-model="form.reporter_email"
                                type="email"
                                placeholder="tu@correo.com"
                            />
                            <p
                                v-if="form.errors.reporter_email"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.reporter_email }}
                            </p>
                        </div>

                        <div class="flex items-start gap-2 rounded-md border bg-muted/40 p-3 text-xs text-muted-foreground">
                            <ShieldAlert class="mt-0.5 size-4 shrink-0" />
                            <p>
                                Envía un reporte por terraza. El formulario está
                                limitado a cinco envíos por minuto.
                            </p>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            Enviar reporte
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </PublicShell>
</template>
