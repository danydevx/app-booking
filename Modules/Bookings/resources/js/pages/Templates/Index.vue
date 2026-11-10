<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type EmailTemplate = {
    id: number;
    name: string;
    subject: string;
    body: string;
    update_url: string;
    delete_url: string;
};

const props = defineProps<{
    team: { name: string; slug: string };
    templates: EmailTemplate[];
    urls: {
        index: string;
        store: string;
        customers: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Solicitudes', href: '/' },
            { title: 'Plantillas de correo' },
        ],
    },
});

const form = useForm({
    name: '',
    subject: '',
    body: '',
});

const editing = ref<EmailTemplate | null>(null);

function submit(): void {
    if (editing.value) {
        form.patch(editing.value.update_url, {
            preserveScroll: true,
            onSuccess: reset,
        });

        return;
    }

    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: reset,
    });
}

function edit(template: EmailTemplate): void {
    editing.value = template;
    form.name = template.name;
    form.subject = template.subject;
    form.body = template.body;
    form.clearErrors();
}

function reset(): void {
    editing.value = null;
    form.reset();
}

function destroy(template: EmailTemplate): void {
    if (!window.confirm(`¿Eliminar la plantilla ${template.name}?`)) {
        return;
    }

    router.delete(template.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Plantillas de correo" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Plantillas de correo</h1>
                <p class="text-sm text-muted-foreground">
                    Mensajes reutilizables para dar seguimiento a clientes de
                    {{ team.name }}.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="urls.customers">Clientes</Link>
            </Button>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-lg font-semibold">
                    {{ editing ? 'Editar plantilla' : 'Nueva plantilla' }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Variables disponibles:
                    <code v-pre>{{ nombre }}</code
                    >, <code v-pre>{{ correo }}</code
                    >, <code v-pre>{{ terraza }}</code
                    >, <code v-pre>{{ fechas }}</code
                    >, <code v-pre>{{ empresa }}</code
                    >.
                </p>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="template-name">Nombre</Label>
                        <Input
                            id="template-name"
                            v-model="form.name"
                            maxlength="80"
                            required
                            placeholder="Ej. Seguimiento inicial"
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="template-subject">Asunto</Label>
                        <Input
                            id="template-subject"
                            v-model="form.subject"
                            maxlength="200"
                            required
                            placeholder="Seguimiento a cliente"
                        />
                        <p
                            v-if="form.errors.subject"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="template-body">Mensaje</Label>
                        <textarea
                            id="template-body"
                            v-model="form.body"
                            rows="10"
                            maxlength="20000"
                            required
                            class="min-h-48 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Hola, te contactamos sobre la terraza."
                        />
                        <p
                            v-if="form.errors.body"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.body }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{
                                editing ? 'Guardar cambios' : 'Crear plantilla'
                            }}
                        </Button>
                        <Button
                            v-if="editing"
                            type="button"
                            variant="secondary"
                            @click="reset"
                        >
                            Cancelar
                        </Button>
                    </div>
                </form>
            </section>

            <section class="space-y-3">
                <div
                    v-if="templates.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center"
                >
                    <h2 class="font-medium">No hay plantillas</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Crea la primera para reutilizarla desde la ficha de un
                        cliente.
                    </p>
                </div>

                <article
                    v-for="template in templates"
                    :key="template.id"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h2 class="font-semibold">{{ template.name }}</h2>
                            <p class="text-sm text-muted-foreground">
                                {{ template.subject }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="edit(template)"
                            >
                                Editar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="destroy(template)"
                            >
                                Eliminar
                            </Button>
                        </div>
                    </div>
                    <p
                        class="mt-3 line-clamp-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ template.body }}
                    </p>
                </article>
            </section>
        </div>
    </div>
</template>
