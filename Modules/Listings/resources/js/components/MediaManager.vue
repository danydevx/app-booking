<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    GripVertical,
    ImagePlus,
    LoaderCircle,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ListingLimits, ListingMedia } from '../types';

const props = defineProps<{
    media: ListingMedia[];
    limits: ListingLimits;
    urls: {
        media_store: string;
        media_reorder: string;
    };
}>();

const form = useForm<{
    file: File | null;
    alt_text: string;
}>({
    file: null,
    alt_text: '',
});

const items = ref<ListingMedia[]>([...props.media]);
const fileInput = ref<HTMLInputElement | null>(null);
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);
const selectedPreview = ref<string | null>(null);

const uploadPercentage = computed(() => form.progress?.percentage ?? 0);
const hasUploadProgress = computed(
    () => form.progress?.percentage !== undefined,
);
const selectedFileSize = computed(() => {
    const bytes = form.file?.size ?? 0;

    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
});

const hasVideos = computed(() =>
    items.value.some((item) => item.type === 'video'),
);

watch(
    () => props.media,
    (media) => {
        items.value = [...media];
    },
    { deep: true },
);

function releasePreview(): void {
    if (selectedPreview.value) {
        URL.revokeObjectURL(selectedPreview.value);
        selectedPreview.value = null;
    }
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    form.file = file;
    form.clearErrors('file');
    releasePreview();

    if (file) {
        selectedPreview.value = URL.createObjectURL(file);
    }
}

function clearSelectedFile(): void {
    form.file = null;
    form.clearErrors('file');
    releasePreview();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function upload(): void {
    form.post(props.urls.media_store, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file', 'alt_text');
            clearSelectedFile();
        },
    });
}

onBeforeUnmount(releasePreview);

function move(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= items.value.length) {
        return;
    }

    const next = [...items.value];
    [next[index], next[target]] = [next[target], next[index]];

    items.value = next;
    persistOrder();
}

function onDragStart(index: number, event: DragEvent): void {
    draggedIndex.value = index;
    dragOverIndex.value = index;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
    }
}

function onDragOver(index: number, event: DragEvent): void {
    event.preventDefault();
    dragOverIndex.value = index;

    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
}

function onDrop(index: number): void {
    const source = draggedIndex.value;

    draggedIndex.value = null;
    dragOverIndex.value = null;

    if (source === null || source === index) {
        return;
    }

    const next = [...items.value];
    const [moved] = next.splice(source, 1);
    next.splice(index, 0, moved);

    items.value = next;
    persistOrder();
}

function onDragEnd(): void {
    draggedIndex.value = null;
    dragOverIndex.value = null;
}

function persistOrder(): void {
    router.patch(
        props.urls.media_reorder,
        { ids: items.value.map((item) => item.id) },
        {
            preserveScroll: true,
            onError: () => {
                items.value = [...props.media];
            },
        },
    );
}

function remove(media: ListingMedia): void {
    if (!media.delete_url) {
        return;
    }

    if (!window.confirm('¿Eliminar este archivo?')) {
        return;
    }

    router.delete(media.delete_url, { preserveScroll: true });
}
</script>

<template>
    <section class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">
                    {{ hasVideos ? 'Fotos y videos' : 'Fotos' }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    Máximo {{ limits.max_images }} imágenes según tu plan. La
                    primera imagen será la portada.
                </p>
            </div>
            <Badge variant="secondary">
                {{ items.length }} / {{ limits.max_images }}
            </Badge>
        </div>

        <form
            class="grid gap-4 rounded-xl border bg-muted/20 p-4"
            @submit.prevent="upload"
            :aria-busy="form.processing"
        >
            <div class="grid gap-2">
                <Label for="media-file">Agregar imagen</Label>
                <input
                    id="media-file"
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    :disabled="form.processing"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1 file:text-sm file:font-medium focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-60"
                    @change="onFileChange"
                />
                <p class="text-xs text-muted-foreground">
                    JPG, PNG o WebP. Máximo
                    {{ Math.round(limits.max_image_kb / 1024) }} MB.
                </p>
                <InputError :message="form.errors.file" />
            </div>

            <div
                v-if="selectedPreview && form.file"
                class="flex flex-wrap items-center gap-3 rounded-lg border bg-background p-3"
            >
                <img
                    :src="selectedPreview"
                    :alt="form.file.name"
                    class="size-16 rounded-md object-cover"
                />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ form.file.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ selectedFileSize }} · Lista para subir
                    </p>
                </div>
                <Button
                    type="button"
                    size="icon-sm"
                    variant="ghost"
                    :disabled="form.processing"
                    title="Quitar imagen"
                    @click="clearSelectedFile"
                >
                    <X class="size-4" />
                </Button>
            </div>

            <div
                class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
            >
                <div class="grid gap-2">
                    <Label for="media-alt">Texto alternativo (opcional)</Label>
                    <Input
                        id="media-alt"
                        v-model="form.alt_text"
                        maxlength="150"
                        :disabled="form.processing"
                        placeholder="Terraza con mesas y luces"
                    />
                    <InputError :message="form.errors.alt_text" />
                </div>
                <Button type="submit" :disabled="form.processing || !form.file">
                    <LoaderCircle
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <ImagePlus v-else class="size-4" />
                    {{
                        form.processing
                            ? 'Subiendo…'
                            : form.file
                              ? 'Subir imagen'
                              : 'Selecciona una imagen'
                    }}
                </Button>
            </div>

            <div v-if="form.processing" class="space-y-1" aria-live="polite">
                <div
                    class="flex items-center justify-between gap-2 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5">
                        <LoaderCircle class="size-3.5 animate-spin" />
                        Subiendo imagen…
                    </span>
                    <span class="tabular-nums">
                        {{ hasUploadProgress ? `${uploadPercentage}%` : '' }}
                    </span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary transition-all duration-300"
                        :class="{ 'animate-pulse': !hasUploadProgress }"
                        :style="{
                            width: hasUploadProgress
                                ? `${Math.max(5, uploadPercentage)}%`
                                : '100%',
                        }"
                    />
                </div>
            </div>
        </form>

        <p class="flex items-center gap-2 text-xs text-muted-foreground">
            <GripVertical class="size-4 shrink-0" />
            Arrastra una tarjeta para cambiar el orden. La primera será la
            portada.
        </p>

        <div
            v-if="items.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="(item, index) in items"
                :key="item.id"
                class="overflow-hidden rounded-xl border bg-card transition-shadow"
                :class="{
                    'opacity-50': draggedIndex === index,
                    'border-primary/60 shadow-sm':
                        dragOverIndex === index &&
                        draggedIndex !== null &&
                        draggedIndex !== index,
                }"
                @dragover="onDragOver(index, $event)"
                @drop="onDrop(index)"
            >
                <div class="relative aspect-[4/3] bg-muted">
                    <img
                        v-if="item.type === 'image'"
                        :src="item.url"
                        :alt="item.alt_text ?? ''"
                        class="h-full w-full object-cover"
                    />
                    <video
                        v-else
                        :src="item.url"
                        controls
                        class="h-full w-full object-cover"
                    />

                    <div
                        class="absolute inset-x-3 top-3 flex items-center justify-between gap-2"
                    >
                        <button
                            type="button"
                            draggable="true"
                            class="inline-flex cursor-grab items-center gap-1 rounded-md bg-background/90 px-2 py-1 text-xs font-medium shadow-sm active:cursor-grabbing"
                            title="Arrastrar para reordenar"
                            @dragstart="onDragStart(index, $event)"
                            @dragend="onDragEnd"
                        >
                            <GripVertical class="size-3.5" />
                            {{ index === 0 ? 'Portada' : `#${index + 1}` }}
                        </button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="secondary"
                            class="bg-background/90 shadow-sm"
                            title="Eliminar archivo"
                            @click="remove(item)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="space-y-3 p-3">
                    <p
                        v-if="item.alt_text"
                        class="line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ item.alt_text }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        {{ item.type_label }}
                    </p>

                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-muted-foreground">
                            {{ item.type_label }}
                        </span>
                        <div class="flex gap-1">
                            <Button
                                type="button"
                                size="icon-sm"
                                variant="ghost"
                                :disabled="index === 0"
                                title="Mover antes"
                                @click="move(index, -1)"
                            >
                                <ArrowUp class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon-sm"
                                variant="ghost"
                                :disabled="index === items.length - 1"
                                title="Mover después"
                                @click="move(index, 1)"
                            >
                                <ArrowDown class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </article>
        </div>
        <p
            v-else
            class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
        >
            Todavía no hay fotos. Agrega una imagen para poder publicar.
        </p>
    </section>
</template>
