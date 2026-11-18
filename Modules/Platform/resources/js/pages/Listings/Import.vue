<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Download, FileUp, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Labeled = { value: string; label: string };

type PreviewRow = {
    line: number;
    name: string | null;
    capacity: number | null;
    event_types: Labeled[];
    services: Labeled[];
    status: 'create' | 'update' | 'error';
    errors: string[];
    warnings: string[];
};

type Pending = {
    id: string;
    filename: string;
    rows: PreviewRow[];
    summary: { total: number; create: number; update: number; error: number };
};

type ReportRow = {
    line: number;
    name: string | null;
    status: 'created' | 'updated' | 'error';
    message: string;
    url: string | null;
};

type Report = {
    created: number;
    updated: number;
    failed: number;
    rows: ReportRow[];
};

const props = defineProps<{
    directory: { name: string; listings_url: string } | null;
    pending: Pending | null;
    report: Report | null;
    template_urls: { csv: string; json: string };
    urls: {
        index: string;
        preview: string;
        store: string;
        cancel: string;
    };
}>();

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm<{ file: File | null }>({ file: null });
const confirmForm = useForm({});

const importable = computed(() =>
    props.pending
        ? props.pending.summary.create + props.pending.summary.update
        : 0,
);

function onFile(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function submit(): void {
    form.post(props.urls.preview, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file');

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

function confirm(): void {
    confirmForm.post(props.urls.store, { preserveScroll: true });
}

function cancel(): void {
    router.delete(props.urls.cancel, { preserveScroll: true });
}

function statusVariant(
    status: PreviewRow['status'],
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'error') {
        return 'destructive';
    }

    return status === 'update' ? 'outline' : 'secondary';
}

function statusLabel(status: PreviewRow['status']): string {
    if (status === 'error') {
        return 'Error';
    }

    return status === 'update' ? 'Actualizará' : 'Lista';
}
</script>

<template>
    <Head title="Importar terrazas · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Importar terrazas</h1>
                <p class="max-w-3xl text-sm text-muted-foreground">
                    Carga un CSV o JSON con los datos generales de cada terraza
                    (título, capacidad, descripción, tipos de evento y
                    características). Se crean en el
                    {{ directory?.name ?? 'Directorio' }} como borradores
                    reclamables por su dueño.
                </p>
            </div>

            <Button variant="outline" as-child>
                <Link :href="urls.index">Volver a terrazas</Link>
            </Button>
        </div>

        <div
            v-if="!directory"
            class="rounded-xl border border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive"
        >
            <p class="font-medium">Falta la cuenta Directorio</p>
            <p class="mt-1">
                Crea la cuenta con
                <code class="font-mono">php artisan app:directory-team</code> y
                recarga esta página.
            </p>
        </div>

        <section class="rounded-xl border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">1. Sube tu archivo</h2>
                    <p class="text-sm text-muted-foreground">
                        CSV con encabezados o JSON con
                        <code class="font-mono">listings</code>. Máximo 2 MB y
                        500 filas. Los valores múltiples se separan con
                        <code class="font-mono">|</code>.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="template_urls.csv">
                            <Download />
                            Plantilla CSV
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <a :href="template_urls.json">
                            <Download />
                            Plantilla JSON
                        </a>
                    </Button>
                </div>
            </div>

            <form
                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                @submit.prevent="submit"
            >
                <div class="flex-1">
                    <label
                        for="listing-import-file"
                        class="mb-1 block text-sm font-medium"
                    >
                        Archivo
                    </label>
                    <input
                        id="listing-import-file"
                        ref="fileInput"
                        type="file"
                        accept=".csv,.json,.txt,text/csv,application/json,text/plain"
                        class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                        @change="onFile"
                    />
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>

                <Button type="submit" :disabled="form.processing || !directory">
                    <FileUp />
                    {{ form.processing ? 'Leyendo…' : 'Vista previa' }}
                </Button>
            </form>
        </section>

        <section v-if="pending" class="rounded-xl border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">
                        2. Revisa la vista previa
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ pending.filename }} ·
                        {{ pending.summary.total }}
                        {{ pending.summary.total === 1 ? 'fila' : 'filas' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Badge variant="secondary">
                        {{ pending.summary.create }} por crear
                    </Badge>
                    <Badge variant="outline">
                        {{ pending.summary.update }} por actualizar
                    </Badge>
                    <Badge variant="destructive">
                        {{ pending.summary.error }} con error
                    </Badge>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead
                        class="bg-muted/50 text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-3 py-2">Fila</th>
                            <th class="px-3 py-2">Terraza</th>
                            <th class="px-3 py-2">Capacidad</th>
                            <th class="px-3 py-2">Eventos</th>
                            <th class="px-3 py-2">Características</th>
                            <th class="px-3 py-2">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y align-top">
                        <tr v-for="row in pending.rows" :key="row.line">
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ row.line }}
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-medium">
                                    {{ row.name ?? 'Sin título' }}
                                </p>

                                <ul
                                    v-if="row.errors.length"
                                    class="mt-1 space-y-1 text-xs text-destructive"
                                >
                                    <li
                                        v-for="error in row.errors"
                                        :key="error"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>

                                <ul
                                    v-if="row.warnings.length"
                                    class="mt-1 space-y-1 text-xs text-amber-600 dark:text-amber-400"
                                >
                                    <li
                                        v-for="warning in row.warnings"
                                        :key="warning"
                                    >
                                        {{ warning }}
                                    </li>
                                </ul>
                            </td>
                            <td class="px-3 py-3">
                                {{ row.capacity ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <div
                                    v-if="row.event_types.length"
                                    class="flex max-w-64 flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="item in row.event_types"
                                        :key="item.value"
                                        variant="secondary"
                                    >
                                        {{ item.label }}
                                    </Badge>
                                </div>
                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div
                                    v-if="row.services.length"
                                    class="flex max-w-72 flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="item in row.services"
                                        :key="item.value"
                                        variant="outline"
                                    >
                                        {{ item.label }}
                                    </Badge>
                                </div>
                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <Badge :variant="statusVariant(row.status)">
                                    {{ statusLabel(row.status) }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">
                    Las filas con error se omiten; puedes corregirlas y volver a
                    importarlas.
                </p>

                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="confirmForm.processing"
                        @click="cancel"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        :disabled="confirmForm.processing || importable === 0"
                        @click="confirm"
                    >
                        <Upload />
                        {{
                            confirmForm.processing
                                ? 'Importando…'
                                : `Importar ${importable} ${importable === 1 ? 'terraza' : 'terrazas'}`
                        }}
                    </Button>
                </div>
            </div>
        </section>

        <section
            v-if="report"
            class="rounded-xl border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
        >
            <h2 class="text-lg font-semibold">3. Resultado</h2>
            <p class="mt-1">
                {{ report.created }} creadas · {{ report.updated }} actualizadas
                · {{ report.failed }} con error.
            </p>

            <ul class="mt-3 space-y-1">
                <li
                    v-for="row in report.rows"
                    :key="`${row.line}-${row.name}`"
                    class="flex flex-wrap items-baseline gap-2"
                >
                    <span
                        class="text-xs text-emerald-800/70 dark:text-emerald-200/70"
                    >
                        Fila {{ row.line }}
                    </span>
                    <Link
                        v-if="row.url"
                        :href="row.url"
                        class="font-medium underline underline-offset-2"
                    >
                        {{ row.name }}
                    </Link>
                    <span v-else class="font-medium">
                        {{ row.name ?? 'Sin título' }}
                    </span>
                    <span v-if="row.message" class="text-xs">
                        {{ row.message }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
