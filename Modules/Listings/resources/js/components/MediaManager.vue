<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
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
    type: 'image' | 'video';
    file: File | null;
    alt_text: string;
}>({
    type: 'image',
    file: null,
    alt_text: '',
});

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function upload(): void {
    form.post(props.urls.media_store, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file', 'alt_text');
        },
    });
}

function move(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= props.media.length) {
        return;
    }

    const ids = props.media.map((item) => item.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];

    router.patch(props.urls.media_reorder, { ids }, { preserveScroll: true });
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
                <h2 class="text-lg font-semibold">Fotos y videos</h2>
                <p class="text-sm text-muted-foreground">
                    Máximo {{ limits.max_images }} imágenes
                    <span v-if="limits.max_videos > 0">
                        y {{ limits.max_videos }} videos
                    </span>
                    según tu plan. La primera imagen será la portada.
                </p>
            </div>
        </div>

        <form
            class="grid gap-4 rounded-xl border p-4 sm:grid-cols-[10rem_1fr_auto] sm:items-end"
            @submit.prevent="upload"
        >
            <div class="grid gap-2">
                <Label for="media-type">Tipo</Label>
                <select
                    id="media-type"
                    v-model="form.type"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="image">Imagen</option>
                    <option v-if="limits.max_videos > 0" value="video">
                        Video
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="media-file">Archivo</Label>
                <Input
                    id="media-file"
                    type="file"
                    :accept="form.type === 'image' ? 'image/*' : 'video/*'"
                    @change="onFileChange"
                />
                <InputError :message="form.errors.file" />
            </div>

            <Button type="submit" :disabled="form.processing || !form.file">
                Subir
            </Button>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="media-alt">Texto alternativo (opcional)</Label>
                <Input
                    id="media-alt"
                    v-model="form.alt_text"
                    maxlength="150"
                    placeholder="Terraza con mesas y luces"
                />
                <InputError :message="form.errors.alt_text" />
            </div>
        </form>

        <div
            v-if="media.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="(item, index) in media"
                :key="item.id"
                class="overflow-hidden rounded-xl border"
            >
                <div class="aspect-video bg-muted">
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
                </div>

                <div class="space-y-3 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">
                            {{ item.type_label }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            #{{ index + 1 }}
                        </span>
                    </div>
                    <p
                        v-if="item.alt_text"
                        class="line-clamp-2 text-xs text-muted-foreground"
                    >
                        {{ item.alt_text }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            :disabled="index === 0"
                            @click="move(index, -1)"
                        >
                            Subir
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            :disabled="index === media.length - 1"
                            @click="move(index, 1)"
                        >
                            Bajar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            @click="remove(item)"
                        >
                            Eliminar
                        </Button>
                    </div>
                </div>
            </article>
        </div>
        <p
            v-else
            class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
        >
            Todavía no hay archivos. Agrega una imagen para poder publicar.
        </p>
    </section>
</template>
