<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    CustomerActivity,
    CustomerDetail,
    CustomerTag,
    CustomerTask,
    InquiryStatus,
    StatusOption,
} from '../../types';

const props = defineProps<{
    customer: CustomerDetail;
    activities: CustomerActivity[];
    activityTypes: StatusOption[];
    tasks: CustomerTask[];
    taskPriorities: StatusOption[];
    teamMembers: Array<{ id: number; name: string }>;
    tags: Array<
        CustomerTag & {
            customers_count: number;
            delete_url: string | null;
        }
    >;
    canManageTags: boolean;
    pipelineStages: StatusOption[];
    activeListing: { id: number; name: string; slug: string } | null;
    management: {
        manage: string;
        edit: string;
        bookings: string;
        customers: string;
        availability: string;
        listings: string;
    } | null;
    isArchived: boolean;
    isAnonymized: boolean;
    canManageLifecycle: boolean;
    canMerge: boolean;
    canAnonymize: boolean;
    mergeCandidates: Array<{ id: number; name: string; email: string }>;
    metrics: {
        total_inquiries: number;
        accepted_inquiries: number;
        upcoming_bookings: number;
        deposits_received_cents: number;
        last_activity_at: string | null;
        next_task: {
            id: number;
            title: string;
            due_at: string | null;
            priority_label: string;
        } | null;
    };
    urls: {
        index: string;
        update: string;
        activities_store: string;
        tasks_store: string;
        tags_store: string;
        tags_sync: string;
        pipeline_update: string;
        merge: string;
        archive: string;
        restore: string;
        anonymize: string;
    };
}>();

defineOptions({
    layout: (props: {
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
                      { title: 'Ficha' },
                  ]
                : [
                      { title: 'Solicitudes', href: '/' },
                      { title: 'Clientes' },
                      { title: 'Ficha' },
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

const form = useForm({
    name: props.customer.name,
    email: props.customer.email,
    phone: props.customer.phone ?? '',
    whatsapp_phone: props.customer.whatsapp_phone ?? '',
    notes: props.customer.notes ?? '',
});

const activityForm = useForm({
    type: 'note',
    body: '',
    occurred_at: '',
});

const taskForm = useForm({
    title: '',
    notes: '',
    due_at: '',
    priority: 'normal',
    assigned_to: '',
});

const editingTask = ref<CustomerTask | null>(null);
const activityBody = ref<HTMLTextAreaElement | null>(null);

const tagForm = useForm({
    tag_ids: props.customer.tags.map((tag) => tag.id),
});

const tagCreateForm = useForm({
    name: '',
    color: '#64748b',
});

const pipelineForm = useForm({
    stage: props.customer.pipeline_stage,
});

const mergeForm = useForm({
    target_id: '',
    reason: '',
});

const anonymizeForm = useForm({
    reason: '',
});

function submit(): void {
    form.patch(props.urls.update, { preserveScroll: true });
}

function submitTask(): void {
    if (editingTask.value) {
        taskForm.patch(editingTask.value.update_url, {
            preserveScroll: true,
            onSuccess: resetTaskForm,
        });

        return;
    }

    taskForm.post(props.urls.tasks_store, {
        preserveScroll: true,
        onSuccess: resetTaskForm,
    });
}

function editTask(task: CustomerTask): void {
    editingTask.value = task;
    taskForm.title = task.title;
    taskForm.notes = task.notes ?? '';
    taskForm.due_at = task.due_at ? task.due_at.slice(0, 16) : '';
    taskForm.priority = task.priority;
    taskForm.assigned_to =
        task.assignee_id !== null ? String(task.assignee_id) : '';
    taskForm.clearErrors();
}

function resetTaskForm(): void {
    editingTask.value = null;
    taskForm.reset();
    taskForm.priority = 'normal';
}

function saveTags(): void {
    tagForm.patch(props.urls.tags_sync, { preserveScroll: true });
}

function savePipeline(): void {
    pipelineForm.patch(props.urls.pipeline_update, { preserveScroll: true });
}

function mergeCustomer(): void {
    if (
        !window.confirm(
            '¿Fusionar este cliente con el destino? El cliente actual quedará archivado.',
        )
    ) {
        return;
    }

    mergeForm.post(props.urls.merge);
}

function archiveCustomer(): void {
    if (!window.confirm('¿Archivar este cliente?')) {
        return;
    }

    router.patch(props.urls.archive, {}, { preserveScroll: true });
}

function restoreCustomer(): void {
    router.patch(props.urls.restore, {}, { preserveScroll: true });
}

function anonymizeCustomer(): void {
    if (
        !window.confirm(
            '¿Anonimizar este cliente? Se eliminarán sus datos personales y los de sus solicitudes. Esta acción no se puede deshacer.',
        )
    ) {
        return;
    }

    anonymizeForm.patch(props.urls.anonymize);
}

function toggleTag(tagId: number): void {
    tagForm.tag_ids = tagForm.tag_ids.includes(tagId)
        ? tagForm.tag_ids.filter((id) => id !== tagId)
        : [...tagForm.tag_ids, tagId];
}

function createTag(): void {
    tagCreateForm.post(props.urls.tags_store, {
        preserveScroll: true,
        onSuccess: () => tagCreateForm.reset(),
    });
}

function deleteTag(tag: CustomerTag & { delete_url: string | null }): void {
    if (
        !tag.delete_url ||
        !window.confirm(`¿Eliminar la etiqueta ${tag.name}?`)
    ) {
        return;
    }

    router.delete(tag.delete_url, { preserveScroll: true });
}

function completeTask(task: CustomerTask): void {
    router.patch(task.complete_url, {}, { preserveScroll: true });
}

function cancelTask(task: CustomerTask): void {
    if (!window.confirm('¿Cancelar esta tarea?')) {
        return;
    }

    router.patch(task.cancel_url, {}, { preserveScroll: true });
}

function recordActivity(): void {
    activityForm.post(props.urls.activities_store, {
        preserveScroll: true,
        onSuccess: () => {
            activityForm.reset('body', 'occurred_at');
            activityForm.type = 'note';
        },
    });
}

function deleteActivity(activity: CustomerActivity): void {
    if (!window.confirm('¿Eliminar esta actividad del historial?')) {
        return;
    }

    router.delete(activity.delete_url, { preserveScroll: true });
}

function formatActivityDate(value: string): string {
    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function formatMoney(cents: number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(cents / 100);
}

const mailtoUrl = computed(() => {
    const subject = encodeURIComponent(`Seguimiento a ${props.customer.name}`);
    const body = encodeURIComponent(
        `Hola ${props.customer.name},\n\nTe contacto para dar seguimiento a tu solicitud.\n\nSaludos.`,
    );

    return `mailto:${props.customer.email}?subject=${subject}&body=${body}`;
});

const whatsappUrl = computed<string | null>(() => {
    const phone = props.customer.whatsapp_phone ?? props.customer.phone;

    if (!phone) {
        return null;
    }

    const digits = phone.replace(/\D/g, '');
    const message = encodeURIComponent(
        `Hola ${props.customer.name}, te contacto para dar seguimiento a tu solicitud.`,
    );

    return digits === '' ? null : `https://wa.me/${digits}?text=${message}`;
});

function startActivity(type: string): void {
    activityForm.type = type;

    void nextTick(() => {
        activityBody.value?.focus();
        document
            .getElementById('timeline')
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function statusVariant(
    status: InquiryStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (
        status === 'declined' ||
        status === 'cancelled' ||
        status === 'expired'
    ) {
        return 'destructive';
    }

    if (status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head :title="customer.name" />

    <div class="flex w-full flex-col gap-6 p-4">
        <header class="rounded-xl border bg-card p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            {{ customer.name }}
                        </h1>
                        <Badge variant="outline">
                            {{ customer.pipeline_stage_label }}
                        </Badge>
                        <Badge v-if="isArchived" variant="secondary">
                            Archivado
                        </Badge>
                        <Badge v-if="isAnonymized" variant="destructive">
                            Anonimizado
                        </Badge>
                        <Badge
                            v-if="customer.tags.length > 0"
                            variant="secondary"
                        >
                            {{ customer.tags.length }}
                            {{
                                customer.tags.length === 1
                                    ? 'etiqueta'
                                    : 'etiquetas'
                            }}
                        </Badge>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                    >
                        <a
                            class="underline underline-offset-4 hover:text-foreground"
                            :href="`mailto:${customer.email}`"
                        >
                            {{ customer.email }}
                        </a>
                        <a
                            v-if="customer.phone"
                            class="underline underline-offset-4 hover:text-foreground"
                            :href="`tel:${customer.phone}`"
                        >
                            {{ customer.phone }}
                        </a>
                        <span v-else>Sin teléfono</span>
                        <span v-if="metrics.last_activity_at">
                            Última actividad:
                            {{ formatActivityDate(metrics.last_activity_at) }}
                        </span>
                    </div>

                    <div
                        v-if="customer.tags.length > 0"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="tag in customer.tags"
                            :key="tag.id"
                            class="rounded-full px-2.5 py-1 text-xs font-medium text-white"
                            :style="{ backgroundColor: tag.color }"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <a :href="mailtoUrl">Enviar correo</a>
                    </Button>
                    <Button v-if="whatsappUrl" variant="outline" as-child>
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            WhatsApp
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="urls.index">Volver</Link>
                    </Button>
                </div>
            </div>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Solicitudes</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ metrics.total_inquiries }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ metrics.accepted_inquiries }} aceptadas
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Reservas próximas</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ metrics.upcoming_bookings }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Anticipos recibidos</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ formatMoney(metrics.deposits_received_cents) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Próxima tarea</p>
                <p class="mt-1 truncate text-sm font-medium">
                    {{ metrics.next_task?.title ?? 'Sin tareas pendientes' }}
                </p>
                <p
                    v-if="metrics.next_task"
                    class="text-xs text-muted-foreground"
                >
                    {{ metrics.next_task.priority_label }}
                    <span v-if="metrics.next_task.due_at">
                        · {{ formatActivityDate(metrics.next_task.due_at) }}
                    </span>
                </p>
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            <div class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold">Tareas</h2>
                        <p class="text-sm text-muted-foreground">
                            Define el siguiente paso con este cliente.
                        </p>
                    </div>

                    <form
                        class="grid gap-3 rounded-lg border bg-muted/20 p-4 sm:grid-cols-2"
                        @submit.prevent="submitTask"
                    >
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="task-title">Título</Label>
                            <Input
                                id="task-title"
                                v-model="taskForm.title"
                                maxlength="160"
                                required
                                placeholder="Ej. Confirmar número de invitados"
                            />
                            <p
                                v-if="taskForm.errors.title"
                                class="text-sm text-destructive"
                            >
                                {{ taskForm.errors.title }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-due">Vencimiento</Label>
                            <Input
                                id="task-due"
                                v-model="taskForm.due_at"
                                type="datetime-local"
                            />
                            <p
                                v-if="taskForm.errors.due_at"
                                class="text-sm text-destructive"
                            >
                                {{ taskForm.errors.due_at }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-priority">Prioridad</Label>
                            <select
                                id="task-priority"
                                v-model="taskForm.priority"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option
                                    v-for="priority in taskPriorities"
                                    :key="priority.value"
                                    :value="priority.value"
                                >
                                    {{ priority.label }}
                                </option>
                            </select>
                            <p
                                v-if="taskForm.errors.priority"
                                class="text-sm text-destructive"
                            >
                                {{ taskForm.errors.priority }}
                            </p>
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="task-assignee">Responsable</Label>
                            <select
                                id="task-assignee"
                                v-model="taskForm.assigned_to"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">
                                    Sin asignar (yo al crear)
                                </option>
                                <option
                                    v-for="member in teamMembers"
                                    :key="member.id"
                                    :value="String(member.id)"
                                >
                                    {{ member.name }}
                                </option>
                            </select>
                            <p
                                v-if="taskForm.errors.assigned_to"
                                class="text-sm text-destructive"
                            >
                                {{ taskForm.errors.assigned_to }}
                            </p>
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="task-notes">Notas</Label>
                            <textarea
                                id="task-notes"
                                v-model="taskForm.notes"
                                rows="3"
                                maxlength="2000"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Detalle opcional de la tarea."
                            />
                            <p
                                v-if="taskForm.errors.notes"
                                class="text-sm text-destructive"
                            >
                                {{ taskForm.errors.notes }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:col-span-2">
                            <Button
                                type="submit"
                                :disabled="taskForm.processing"
                            >
                                {{
                                    editingTask
                                        ? 'Guardar cambios'
                                        : 'Crear tarea'
                                }}
                            </Button>
                            <Button
                                v-if="editingTask"
                                type="button"
                                variant="secondary"
                                @click="resetTaskForm"
                            >
                                Cancelar edición
                            </Button>
                        </div>
                    </form>

                    <div v-if="tasks.length > 0" class="mt-5 space-y-3">
                        <article
                            v-for="task in tasks"
                            :key="task.id"
                            class="rounded-lg border p-3"
                            :class="{
                                'border-destructive/40 bg-destructive/5':
                                    task.is_overdue,
                            }"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p class="font-medium">
                                            {{ task.title }}
                                        </p>
                                        <Badge
                                            :variant="
                                                task.status === 'done'
                                                    ? 'secondary'
                                                    : task.status ===
                                                        'cancelled'
                                                      ? 'outline'
                                                      : task.priority === 'high'
                                                        ? 'destructive'
                                                        : 'default'
                                            "
                                        >
                                            {{ task.status_label }}
                                        </Badge>
                                        <Badge
                                            v-if="task.is_overdue"
                                            variant="destructive"
                                        >
                                            Vencida
                                        </Badge>
                                    </div>
                                    <p
                                        v-if="task.notes"
                                        class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                                    >
                                        {{ task.notes }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{
                                            task.due_at
                                                ? formatActivityDate(
                                                      task.due_at,
                                                  )
                                                : 'Sin fecha'
                                        }}
                                        <span v-if="task.assignee">
                                            · {{ task.assignee }}
                                        </span>
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        task.can_manage &&
                                        task.status === 'open'
                                    "
                                    class="flex flex-wrap gap-2"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="editTask(task)"
                                    >
                                        Editar
                                    </Button>
                                    <Button
                                        size="sm"
                                        @click="completeTask(task)"
                                    >
                                        Completar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="cancelTask(task)"
                                    >
                                        Cancelar
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        No hay tareas registradas.
                    </p>
                </section>

                <section id="timeline" class="rounded-xl border bg-card p-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold">Timeline</h2>
                        <p class="text-sm text-muted-foreground">
                            Registra llamadas, correos, reuniones y notas de
                            seguimiento.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button
                                v-for="type in activityTypes"
                                :key="`quick-${type.value}`"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="startActivity(type.value)"
                            >
                                {{ type.label }}
                            </Button>
                        </div>
                    </div>

                    <form
                        class="grid gap-3 rounded-lg border bg-muted/20 p-4 sm:grid-cols-[10rem_1fr]"
                        @submit.prevent="recordActivity"
                    >
                        <div class="grid gap-2">
                            <Label for="activity-type">Tipo</Label>
                            <select
                                id="activity-type"
                                v-model="activityForm.type"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option
                                    v-for="type in activityTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                            <p
                                v-if="activityForm.errors.type"
                                class="text-sm text-destructive"
                            >
                                {{ activityForm.errors.type }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="activity-date">
                                Fecha y hora opcional
                            </Label>
                            <Input
                                id="activity-date"
                                v-model="activityForm.occurred_at"
                                type="datetime-local"
                            />
                            <p
                                v-if="activityForm.errors.occurred_at"
                                class="text-sm text-destructive"
                            >
                                {{ activityForm.errors.occurred_at }}
                            </p>
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="activity-body">Detalle</Label>
                            <textarea
                                id="activity-body"
                                ref="activityBody"
                                v-model="activityForm.body"
                                rows="3"
                                maxlength="5000"
                                required
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Ej. Llamé al cliente para confirmar el número de invitados."
                            />
                            <p
                                v-if="activityForm.errors.body"
                                class="text-sm text-destructive"
                            >
                                {{ activityForm.errors.body }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <Button
                                type="submit"
                                :disabled="activityForm.processing"
                            >
                                Registrar actividad
                            </Button>
                            <p class="mt-2 text-xs text-muted-foreground">
                                El correo y WhatsApp se abren en tu cliente o
                                aplicación externa; la plataforma no los envía
                                automáticamente.
                            </p>
                        </div>
                    </form>

                    <div v-if="activities.length > 0" class="mt-5 space-y-3">
                        <article
                            v-for="activity in activities"
                            :key="activity.id"
                            class="rounded-lg border p-3"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <Badge variant="secondary">
                                        {{ activity.type_label }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{
                                            formatActivityDate(
                                                activity.occurred_at,
                                            )
                                        }}
                                    </span>
                                </div>
                                <Button
                                    v-if="activity.can_delete"
                                    size="sm"
                                    variant="ghost"
                                    @click="deleteActivity(activity)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                            <p class="mt-2 text-sm whitespace-pre-line">
                                {{ activity.body }}
                            </p>
                            <p
                                v-if="activity.author"
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                {{ activity.author }}
                            </p>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Todavía no hay actividades registradas.
                    </p>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 text-lg font-semibold">Solicitudes</h2>
                    <div v-if="customer.inquiries.length > 0" class="space-y-3">
                        <article
                            v-for="inquiry in customer.inquiries"
                            :key="inquiry.id"
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ inquiry.listing.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ inquiry.start_date_label }} al
                                    {{ inquiry.end_date_label }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="statusVariant(inquiry.status)">
                                    {{ inquiry.status_label }}
                                </Badge>
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="inquiry.show_url">Ver</Link>
                                </Button>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Este cliente no tiene solicitudes.
                    </p>
                </section>
            </div>

            <aside class="order-first space-y-6 xl:order-last">
                <section class="rounded-xl border bg-card p-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold">Pipeline</h2>
                        <p class="text-sm text-muted-foreground">
                            Etapa comercial actual del cliente.
                        </p>
                    </div>
                    <form class="grid gap-3" @submit.prevent="savePipeline">
                        <select
                            v-model="pipelineForm.stage"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="stage in pipelineStages"
                                :key="stage.value"
                                :value="stage.value"
                            >
                                {{ stage.label }}
                            </option>
                        </select>
                        <p
                            v-if="pipelineForm.errors.stage"
                            class="text-sm text-destructive"
                        >
                            {{ pipelineForm.errors.stage }}
                        </p>
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="pipelineForm.processing"
                        >
                            Guardar etapa
                        </Button>
                    </form>
                </section>

                <section
                    v-if="canManageLifecycle"
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="text-lg font-semibold">Ciclo de vida</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Archiva, fusiona o anonimiza este cliente.
                    </p>

                    <div class="mt-4">
                        <Button
                            v-if="isArchived"
                            type="button"
                            variant="outline"
                            @click="restoreCustomer"
                        >
                            Restaurar cliente
                        </Button>
                        <Button
                            v-else
                            type="button"
                            variant="outline"
                            @click="archiveCustomer"
                        >
                            Archivar cliente
                        </Button>
                    </div>

                    <details
                        v-if="canMerge && mergeCandidates.length > 0"
                        class="mt-4 border-t pt-4"
                    >
                        <summary class="cursor-pointer text-sm font-medium">
                            Fusionar con otro cliente
                        </summary>
                        <form
                            class="mt-3 grid gap-3"
                            @submit.prevent="mergeCustomer"
                        >
                            <select
                                v-model="mergeForm.target_id"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                required
                            >
                                <option value="">Cliente destino</option>
                                <option
                                    v-for="candidate in mergeCandidates"
                                    :key="candidate.id"
                                    :value="String(candidate.id)"
                                >
                                    {{ candidate.name }} · {{ candidate.email }}
                                </option>
                            </select>
                            <Input
                                v-model="mergeForm.reason"
                                placeholder="Motivo de la fusión"
                                required
                            />
                            <p
                                v-if="mergeForm.errors.target_id"
                                class="text-sm text-destructive"
                            >
                                {{ mergeForm.errors.target_id }}
                            </p>
                            <p
                                v-if="mergeForm.errors.reason"
                                class="text-sm text-destructive"
                            >
                                {{ mergeForm.errors.reason }}
                            </p>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="mergeForm.processing"
                            >
                                Fusionar clientes
                            </Button>
                        </form>
                    </details>

                    <details
                        v-if="canAnonymize && !isAnonymized"
                        class="mt-4 border-t pt-4"
                    >
                        <summary
                            class="cursor-pointer text-sm font-medium text-destructive"
                        >
                            Anonimizar cliente
                        </summary>
                        <form
                            class="mt-3 grid gap-3"
                            @submit.prevent="anonymizeCustomer"
                        >
                            <p class="text-xs text-muted-foreground">
                                Elimina datos personales del cliente y sus
                                solicitudes; conserva reservas y anticipos.
                            </p>
                            <Input
                                v-model="anonymizeForm.reason"
                                placeholder="Motivo de la anonimización"
                                required
                            />
                            <p
                                v-if="anonymizeForm.errors.reason"
                                class="text-sm text-destructive"
                            >
                                {{ anonymizeForm.errors.reason }}
                            </p>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="anonymizeForm.processing"
                            >
                                Anonimizar definitivamente
                            </Button>
                        </form>
                    </details>
                </section>

                <form
                    class="rounded-xl border bg-card p-5"
                    @submit.prevent="submit"
                >
                    <h2 class="mb-4 text-lg font-semibold">
                        Datos del cliente
                    </h2>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nombre</Label>
                            <Input id="name" v-model="form.name" required />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Correo</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Teléfono</Label>
                            <Input id="phone" v-model="form.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="whatsapp">WhatsApp</Label>
                            <Input
                                id="whatsapp"
                                v-model="form.whatsapp_phone"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="notes">Notas internas</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </div>
                    </div>
                    <Button
                        class="mt-4 w-full"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Guardar cliente
                    </Button>
                </form>

                <section class="rounded-xl border bg-card p-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold">Etiquetas</h2>
                        <p class="text-sm text-muted-foreground">
                            Segmenta al cliente para filtrarlo después.
                        </p>
                    </div>

                    <form @submit.prevent="saveTags">
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="tag in tags"
                                :key="tag.id"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                    :checked="tagForm.tag_ids.includes(tag.id)"
                                    @change="toggleTag(tag.id)"
                                />
                                <span
                                    class="size-2.5 rounded-full"
                                    :style="{ backgroundColor: tag.color }"
                                />
                                {{ tag.name }}
                                <span class="text-xs text-muted-foreground">
                                    ({{ tag.customers_count }})
                                </span>
                            </label>
                            <p
                                v-if="tags.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No hay etiquetas creadas.
                            </p>
                        </div>
                        <p
                            v-if="tagForm.errors.tag_ids"
                            class="mt-2 text-sm text-destructive"
                        >
                            {{ tagForm.errors.tag_ids }}
                        </p>
                        <Button
                            v-if="tags.length > 0"
                            class="mt-4"
                            type="submit"
                            :disabled="tagForm.processing"
                        >
                            Guardar etiquetas
                        </Button>
                    </form>

                    <div v-if="canManageTags" class="mt-5 border-t pt-5">
                        <h3 class="text-sm font-medium">Nueva etiqueta</h3>
                        <form
                            class="mt-3 grid gap-3"
                            @submit.prevent="createTag"
                        >
                            <div class="grid gap-2">
                                <Label for="new-tag-name">Nombre</Label>
                                <Input
                                    id="new-tag-name"
                                    v-model="tagCreateForm.name"
                                    maxlength="60"
                                    required
                                    placeholder="Ej. Corporativo"
                                />
                                <p
                                    v-if="tagCreateForm.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ tagCreateForm.errors.name }}
                                </p>
                            </div>
                            <div class="flex items-end gap-3">
                                <div class="grid flex-1 gap-2">
                                    <Label for="new-tag-color">Color</Label>
                                    <input
                                        id="new-tag-color"
                                        v-model="tagCreateForm.color"
                                        type="color"
                                        class="h-9 w-full rounded-md border border-input bg-transparent p-1"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    :disabled="tagCreateForm.processing"
                                >
                                    Crear
                                </Button>
                            </div>
                            <p
                                v-if="tagCreateForm.errors.color"
                                class="text-sm text-destructive"
                            >
                                {{ tagCreateForm.errors.color }}
                            </p>
                        </form>

                        <div v-if="tags.length > 0" class="mt-4 space-y-2">
                            <p class="text-xs text-muted-foreground">
                                Administrar etiquetas
                            </p>
                            <div
                                v-for="tag in tags"
                                :key="`manage-${tag.id}`"
                                class="flex items-center justify-between gap-3 text-sm"
                            >
                                <span>{{ tag.name }}</span>
                                <Button
                                    v-if="tag.delete_url"
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="deleteTag(tag)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
