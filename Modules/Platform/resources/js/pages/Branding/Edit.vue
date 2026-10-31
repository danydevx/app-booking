<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    branding: {
        app_name: string;
        logo_url: string | null;
        support_email: string | null;
        from_address: string | null;
        legal_terms_url: string | null;
        legal_privacy_url: string | null;
        updated_at: string | null;
    };
    urls: {
        update: string;
        dashboard: string;
    };
}>();

const form = useForm({
    app_name: props.branding.app_name,
    support_email: props.branding.support_email ?? '',
    from_address: props.branding.from_address ?? '',
    legal_terms_url: props.branding.legal_terms_url ?? '',
    legal_privacy_url: props.branding.legal_privacy_url ?? '',
    logo: null as File | null,
    remove_logo: false,
});

const preview = ref<string | null>(props.branding.logo_url);

function onLogoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.logo = file;
    form.remove_logo = false;

    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function removeLogo(): void {
    form.logo = null;
    form.remove_logo = true;
    preview.value = null;
}

function submit(): void {
    form.patch(props.urls.update, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Marca de la plataforma" />

    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-semibold">Marca de la plataforma</h1>
            <p class="text-sm text-muted-foreground">
                Nombre, logo, remitente y enlaces legales usados en correos y
                páginas públicas.
            </p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Identidad</CardTitle>
                <CardDescription>
                    Estos valores se aplican a toda la plataforma.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="app_name">Nombre de la aplicación</Label>
                        <Input id="app_name" v-model="form.app_name" required />
                        <p v-if="form.errors.app_name" class="text-sm text-destructive">
                            {{ form.errors.app_name }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">Logo</Label>
                        <div class="flex flex-wrap items-center gap-3">
                            <img
                                v-if="preview"
                                :src="preview"
                                alt="Logo de la plataforma"
                                class="size-16 rounded-md border object-cover"
                            />
                            <input
                                id="logo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="block text-sm"
                                @change="onLogoChange"
                            />
                            <Button
                                v-if="preview"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeLogo"
                            >
                                Quitar logo
                            </Button>
                        </div>
                        <p v-if="form.errors.logo" class="text-sm text-destructive">
                            {{ form.errors.logo }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="from_address">Remitente por defecto</Label>
                            <Input
                                id="from_address"
                                v-model="form.from_address"
                                type="email"
                                required
                            />
                            <p
                                v-if="form.errors.from_address"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.from_address }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="support_email">Correo de soporte</Label>
                            <Input
                                id="support_email"
                                v-model="form.support_email"
                                type="email"
                            />
                            <p
                                v-if="form.errors.support_email"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.support_email }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="legal_terms_url">Términos</Label>
                            <input
                                id="legal_terms_url"
                                v-model="form.legal_terms_url"
                                type="url"
                                placeholder="https://…"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            />
                            <p
                                v-if="form.errors.legal_terms_url"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.legal_terms_url }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="legal_privacy_url">Privacidad</Label>
                            <input
                                id="legal_privacy_url"
                                v-model="form.legal_privacy_url"
                                type="url"
                                placeholder="https://…"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            />
                            <p
                                v-if="form.errors.legal_privacy_url"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.legal_privacy_url }}
                            </p>
                        </div>
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        Guardar marca
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
