<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    team: {
        id: number;
        name: string;
        slug: string;
        timezone: string;
        contact_email: string | null;
        contact_phone: string | null;
        whatsapp_phone: string | null;
        logo_url: string | null;
    };
    urls: {
        update: string;
        edit: string;
    };
}>();

const timezones = [
    'America/Mexico_City',
    'America/Monterrey',
    'America/Cancun',
    'America/Tijuana',
    'America/Merida',
    'America/Chihuahua',
    'America/Hermosillo',
    'America/Bogota',
    'America/Lima',
    'America/Santiago',
    'America/Argentina/Buenos_Aires',
    'Europe/Madrid',
];

const form = useForm({
    name: props.team.name,
    timezone: props.team.timezone,
    contact_email: props.team.contact_email ?? '',
    contact_phone: props.team.contact_phone ?? '',
    whatsapp_phone: props.team.whatsapp_phone ?? '',
    logo: null as File | null,
    remove_logo: false,
});

const preview = ref<string | null>(props.team.logo_url);

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
    <Head title="Marca y contacto" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Marca y contacto"
            description="Logo y datos públicos de la cuenta que se muestran en las terrazas."
        />

        <div>
            <Button variant="outline" as-child>
                <Link :href="urls.edit">Volver a la cuenta</Link>
            </Button>
        </div>

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">Nombre visible</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="logo">Logo</Label>
                <div class="flex flex-wrap items-center gap-3">
                    <img
                        v-if="preview"
                        :src="preview"
                        alt="Logo de la cuenta"
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
                <p class="text-xs text-muted-foreground">
                    JPG, PNG o WebP. Máximo 2 MB.
                </p>
                <InputError :message="form.errors.logo" />
            </div>

            <div class="grid gap-2">
                <Label for="timezone">Zona horaria</Label>
                <select
                    id="timezone"
                    v-model="form.timezone"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option v-for="zone in timezones" :key="zone" :value="zone">
                        {{ zone }}
                    </option>
                </select>
                <InputError :message="form.errors.timezone" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact_email">Correo de contacto</Label>
                    <Input
                        id="contact_email"
                        v-model="form.contact_email"
                        type="email"
                        placeholder="hola@cuenta.com"
                    />
                    <InputError :message="form.errors.contact_email" />
                </div>

                <div class="grid gap-2">
                    <Label for="contact_phone">Teléfono</Label>
                    <Input
                        id="contact_phone"
                        v-model="form.contact_phone"
                        placeholder="+52 55 1234 5678"
                    />
                    <InputError :message="form.errors.contact_phone" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="whatsapp_phone">WhatsApp</Label>
                <Input
                    id="whatsapp_phone"
                    v-model="form.whatsapp_phone"
                    placeholder="+52 55 1234 5678"
                />
                <InputError :message="form.errors.whatsapp_phone" />
            </div>

            <Button type="submit" :disabled="form.processing">Guardar</Button>
        </form>
    </div>
</template>
