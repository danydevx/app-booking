<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ImportResult = {
    created: number;
    skipped: number;
    errors: Array<{ line: number; message: string }>;
};

const props = defineProps<{
    result: ImportResult | null;
    activeListing: { id: number; name: string; slug: string } | null;
    management: {
        manage: string;
        edit: string;
        bookings: string;
        customers: string;
        availability: string;
        listings: string;
    } | null;
    urls: {
        index: string;
        bookings: string;
        store: string;
    };
}>();

defineOptions({
    layout: (props: {
        urls: { index: string; bookings: string };
        activeListing: { name: string } | null;
        management: {
            manage: string;
            edit: string;
            bookings: string;
            customers: string;
            availability: string;
            listings: string;
        } | null;
    }) => ({
        breadcrumbs:
            props.activeListing && props.management
                ? [
                      { title: 'Terrazas', href: props.management.listings },
                      {
                          title: props.activeListing.name,
                          href: props.management.manage,
                      },
                      { title: 'Clientes', href: props.management.customers },
                      { title: 'Importar CSV' },
                  ]
                : [
                      { title: 'Solicitudes', href: props.urls.bookings },
                      { title: 'Clientes', href: props.urls.index },
                      { title: 'Importar CSV' },
                  ],
        submenu:
            props.activeListing && props.management
                ? [
                      { title: 'Resumen', href: props.management.manage },
                      {
                          title: 'Reservaciones',
                          href: props.management.bookings,
                      },
                      { title: 'Clientes', href: props.management.customers },
                      {
                          title: 'Disponibilidad',
                          href: props.management.availability,
                      },
                      { title: 'Fotos y datos', href: props.management.edit },
                  ]
                : [],
    }),
});

const form = useForm<{ file: File | null }>({
    file: null,
});

const template =
    'data:text/csv;charset=utf-8,' +
    encodeURIComponent(
        'Nombre,Correo,Teléfono,WhatsApp,Notas\nAna Pérez,ana@example.com,5555555555,5215555555555,Cliente frecuente\n',
    );

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function submit(): void {
    form.post(props.urls.store, {
        forceFormData: true,
        onSuccess: () => form.reset('file'),
    });
}
</script>

<template>
    <Head title="Importar clientes" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{
                        activeListing
                            ? `Importar clientes · ${activeListing.name}`
                            : 'Importar clientes'
                    }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Carga un CSV con tus clientes. Las direcciones de correo
                    duplicadas se omiten.
                    <template v-if="activeListing">
                        Los clientes nuevos quedarán vinculados a
                        {{ activeListing.name }}.
                    </template>
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.index">Volver</Link>
            </Button>
        </div>

        <div class="rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Formato esperado</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Columnas obligatorias: <strong>Nombre</strong> y
                <strong>Correo</strong>. Opcionales: Teléfono, WhatsApp y Notas.
                Máximo 1,000 filas.
            </p>
            <a
                :href="template"
                download="plantilla-clientes.csv"
                class="mt-3 inline-block text-sm underline underline-offset-4"
            >
                Descargar plantilla CSV
            </a>
        </div>

        <form
            class="grid gap-4 rounded-xl border bg-card p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="csv-file">Archivo CSV</Label>
                <Input
                    id="csv-file"
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    @change="onFileChange"
                />
                <InputError :message="form.errors.file" />
            </div>
            <div>
                <Button type="submit" :disabled="form.processing || !form.file">
                    Importar clientes
                </Button>
            </div>
        </form>

        <section v-if="props.result" class="rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Resultado</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Creados</p>
                    <p class="text-2xl font-semibold">
                        {{ props.result.created }}
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Omitidos</p>
                    <p class="text-2xl font-semibold">
                        {{ props.result.skipped }}
                    </p>
                </div>
                <div class="rounded-lg border p-3">
                    <p class="text-xs text-muted-foreground">Errores</p>
                    <p class="text-2xl font-semibold">
                        {{ props.result.errors.length }}
                    </p>
                </div>
            </div>

            <div v-if="props.result.errors.length > 0" class="mt-4">
                <h3 class="text-sm font-medium">Filas con error</h3>
                <ul class="mt-2 space-y-1 text-sm text-destructive">
                    <li v-for="error in props.result.errors" :key="error.line">
                        Línea {{ error.line }}: {{ error.message }}
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
