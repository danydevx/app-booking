<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, FileCheck2, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
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
        address: string | null;
        capacity: number | null;
        cover_url: string | null;
    };
    roles: Array<{ value: string; label: string }>;
    authenticated: boolean;
    verified: boolean;
    pendingClaim: {
        status: string;
        status_label: string;
        created_at: string | null;
    } | null;
    targetTeams: Array<{
        id: number;
        name: string;
        is_personal: boolean;
    }>;
    urls: {
        store: string;
        listing: string;
        login: string;
        register: string;
        verify: string;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    business_name: props.listing.name,
    role: 'owner',
    claimant_name: user.value?.name ?? '',
    claimant_phone: '',
    whatsapp_phone: '',
    target_team_id: '' as string | number,
    message: '',
    evidence: null as File | null,
    accept_terms: false,
    website: '',
});

function submit(): void {
    form.post(props.urls.store, {
        preserveScroll: true,
        forceFormData: true,
    });
}

const location = computed(
    () =>
        [props.listing.city, props.listing.state].filter(Boolean).join(', ') ||
        'Ubicación por confirmar',
);

const listingError = computed(
    () => (form.errors as Record<string, string | undefined>).listing ?? '',
);
</script>

<template>
    <Head :title="`Reclamar · ${listing.name}`" />

    <PublicShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="space-y-2">
                <Link
                    :href="urls.listing"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Volver a la terraza
                </Link>
                <h1 class="text-3xl font-semibold tracking-tight">
                    ¿Es tu terraza?
                </h1>
                <p class="text-muted-foreground">
                    Reclama el anuncio de {{ listing.name }} para administrar
                    fotos, precios, disponibilidad y solicitudes desde tu
                    cuenta.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ listing.name }}</CardTitle>
                    <CardDescription>{{ location }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <p
                        v-if="listing.address"
                        class="text-sm text-muted-foreground"
                    >
                        {{ listing.address }}
                    </p>

                    <div
                        class="flex items-start gap-3 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground"
                    >
                        <ShieldCheck class="mt-0.5 size-5 shrink-0" />
                        <div class="space-y-1">
                            <p class="font-medium text-foreground">
                                Revisión manual
                            </p>
                            <p>
                                Revisaremos tu información antes de transferir
                                el anuncio. Si necesitamos más datos, te
                                contactaremos por correo o teléfono.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="pendingClaim"
                        class="flex items-start gap-3 rounded-lg border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-900"
                    >
                        <FileCheck2 class="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p class="font-medium">Reclamación en revisión</p>
                            <p>
                                Ya recibimos tu solicitud ({{
                                    pendingClaim.status_label
                                }}). Te avisaremos cuando la hayamos revisado.
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="!authenticated"
                        class="space-y-4 rounded-lg border p-4"
                    >
                        <p class="text-sm">
                            Para reclamar una terraza necesitas una cuenta
                            verificada. Así podemos entregarte el control del
                            anuncio de forma segura.
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button as-child class="sm:flex-1">
                                <Link :href="urls.register">Crear cuenta</Link>
                            </Button>
                            <Button
                                as-child
                                variant="outline"
                                class="sm:flex-1"
                            >
                                <Link :href="urls.login">Iniciar sesión</Link>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-else-if="!verified"
                        class="space-y-4 rounded-lg border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-900"
                    >
                        <p class="font-medium">
                            Verifica tu correo para continuar
                        </p>
                        <p>
                            Enviamos un enlace de verificación a tu correo.
                            Confírmalo y vuelve a esta página para enviar la
                            reclamación.
                        </p>
                        <Button as-child variant="outline">
                            <Link :href="urls.verify">
                                Ir a verificación de correo
                            </Link>
                        </Button>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1 sm:col-span-2">
                                <label
                                    for="business_name"
                                    class="text-sm font-medium"
                                >
                                    Nombre del negocio o cuenta
                                </label>
                                <Input
                                    id="business_name"
                                    v-model="form.business_name"
                                    placeholder="Ej. Jardín Los Encinos"
                                />
                                <p
                                    v-if="form.errors.business_name"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.business_name }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label for="role" class="text-sm font-medium">
                                    Tu relación con la terraza
                                </label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.role"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label
                                    for="target_team_id"
                                    class="text-sm font-medium"
                                >
                                    ¿Dónde administrarás la terraza?
                                </label>
                                <select
                                    id="target_team_id"
                                    v-model="form.target_team_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">
                                        Crear una cuenta nueva con este nombre
                                    </option>
                                    <option
                                        v-for="team in targetTeams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        Usar mi cuenta: {{ team.name }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.target_team_id"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.target_team_id }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label
                                    for="claimant_name"
                                    class="text-sm font-medium"
                                >
                                    Nombre completo
                                </label>
                                <Input
                                    id="claimant_name"
                                    v-model="form.claimant_name"
                                />
                                <p
                                    v-if="form.errors.claimant_name"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.claimant_name }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label
                                    for="claimant_phone"
                                    class="text-sm font-medium"
                                >
                                    Teléfono de contacto
                                </label>
                                <Input
                                    id="claimant_phone"
                                    v-model="form.claimant_phone"
                                    inputmode="tel"
                                    placeholder="33 1234 5678"
                                />
                                <p
                                    v-if="form.errors.claimant_phone"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.claimant_phone }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label
                                    for="whatsapp_phone"
                                    class="text-sm font-medium"
                                >
                                    WhatsApp (opcional)
                                </label>
                                <Input
                                    id="whatsapp_phone"
                                    v-model="form.whatsapp_phone"
                                    inputmode="tel"
                                    placeholder="33 1234 5678"
                                />
                                <p
                                    v-if="form.errors.whatsapp_phone"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.whatsapp_phone }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="message" class="text-sm font-medium">
                                ¿Cómo podemos verificar que eres el propietario?
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Comparte RFC, predial, INE, redes sociales del negocio o cualquier dato que te identifique como responsable."
                            />
                            <p
                                v-if="form.errors.message"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label for="evidence" class="text-sm font-medium">
                                Documento de apoyo (opcional)
                            </label>
                            <input
                                id="evidence"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                                @input="
                                    form.evidence =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                            />
                            <p class="text-xs text-muted-foreground">
                                PDF, JPG, PNG o WebP. Máximo 10 MB. Solo lo verá
                                el equipo de plataforma.
                            </p>
                            <p
                                v-if="form.errors.evidence"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.evidence }}
                            </p>
                        </div>

                        <input
                            v-model="form.website"
                            type="text"
                            tabindex="-1"
                            autocomplete="off"
                            class="hidden"
                            aria-hidden="true"
                        />

                        <label
                            class="flex items-start gap-3 rounded-lg border p-4 text-sm"
                        >
                            <input
                                v-model="form.accept_terms"
                                type="checkbox"
                                class="mt-1 size-4 rounded border-input"
                            />
                            <span>
                                Declaro que soy el propietario o representante
                                autorizado de esta terraza y que la información
                                proporcionada es correcta.
                            </span>
                        </label>
                        <p
                            v-if="form.errors.accept_terms"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.accept_terms }}
                        </p>

                        <p v-if="listingError" class="text-sm text-destructive">
                            {{ listingError }}
                        </p>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            Enviar reclamación
                        </Button>

                        <p
                            class="flex items-center justify-center gap-2 text-xs text-muted-foreground"
                        >
                            <BadgeCheck class="size-4" />
                            Tu correo de cuenta debe estar verificado.
                        </p>
                    </form>
                </CardContent>
            </Card>
        </div>
    </PublicShell>
</template>
