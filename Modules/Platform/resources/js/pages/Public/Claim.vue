<script setup lang="ts">
import '@/homeSite';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Check, FileCheck2, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';

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

const steps = [
    'Comparte tus datos y cómo podemos verificar la propiedad.',
    'El equipo de plataforma revisa la información.',
    'Recibes acceso a la terraza desde tu cuenta.',
];
</script>

<template>
    <Head :title="`Reclamar · ${listing.name}`" />

    <div class="home-container py-10 sm:py-14 lg:py-16">
        <div class="mx-auto max-w-5xl">
            <Link
                :href="urls.listing"
                class="text-sm font-semibold text-[color:var(--home-secondary)] hover:text-[color:var(--home-primary-deep)]"
            >
                ← Volver a la terraza
            </Link>

            <div class="mt-5 max-w-3xl">
                <p class="home-eyebrow">Para propietarios</p>
                <h1
                    class="mt-2 text-3xl leading-tight font-extrabold text-[color:var(--home-dark)] sm:text-4xl"
                >
                    ¿Es tu terraza? Reclama este anuncio
                </h1>
                <p
                    class="mt-3 text-base leading-relaxed text-[color:var(--home-secondary)]"
                >
                    Verifica que eres el propietario o representante autorizado
                    y administra fotos, precios, disponibilidad y solicitudes
                    desde tu cuenta.
                </p>
            </div>

            <div
                class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start lg:gap-8"
            >
                <div class="min-w-0">
                    <div
                        v-if="pendingClaim"
                        class="rounded-[1.125rem] border border-[color:var(--home-primary)]/30 bg-[color:var(--home-soft)] p-6 sm:p-8"
                    >
                        <FileCheck2
                            class="size-8 text-[color:var(--home-primary-deep)]"
                        />
                        <h2
                            class="mt-4 text-xl font-extrabold text-[color:var(--home-primary-deep)]"
                        >
                            Reclamación en revisión
                        </h2>
                        <p
                            class="mt-2 text-sm text-[color:var(--home-primary-deep)]"
                        >
                            Ya recibimos tu solicitud ({{
                                pendingClaim.status_label
                            }}). Revisaremos la información y te avisaremos por
                            correo cuando esté resuelta.
                        </p>
                        <Link
                            :href="urls.listing"
                            class="home-btn home-btn--outline mt-5"
                        >
                            Volver a la terraza
                        </Link>
                    </div>

                    <div
                        v-else-if="!authenticated"
                        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-6 sm:p-8"
                    >
                        <h2 class="text-xl font-extrabold">
                            Primero crea tu cuenta
                        </h2>
                        <p
                            class="mt-2 text-sm leading-relaxed text-[color:var(--home-secondary)]"
                        >
                            Para entregarte el control del anuncio de forma
                            segura necesitas una cuenta con correo verificado.
                        </p>
                        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                            <Link
                                :href="urls.register"
                                class="home-btn home-btn--primary sm:flex-1"
                            >
                                Crear cuenta
                            </Link>
                            <Link
                                :href="urls.login"
                                class="home-btn home-btn--outline sm:flex-1"
                            >
                                Iniciar sesión
                            </Link>
                        </div>
                    </div>

                    <div
                        v-else-if="!verified"
                        class="rounded-[1.125rem] border border-[color:var(--home-primary)]/30 bg-[color:var(--home-soft)] p-6 sm:p-8"
                    >
                        <ShieldCheck
                            class="size-8 text-[color:var(--home-primary-deep)]"
                        />
                        <h2
                            class="mt-4 text-xl font-extrabold text-[color:var(--home-primary-deep)]"
                        >
                            Verifica tu correo para continuar
                        </h2>
                        <p
                            class="mt-2 text-sm text-[color:var(--home-primary-deep)]"
                        >
                            Enviamos un enlace de verificación a tu correo.
                            Confírmalo y vuelve a esta página para enviar la
                            reclamación.
                        </p>
                        <Link
                            :href="urls.verify"
                            class="home-btn home-btn--outline mt-5"
                        >
                            Ir a verificación de correo
                        </Link>
                    </div>

                    <form
                        v-else
                        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-6 sm:p-8"
                        @submit.prevent="submit"
                    >
                        <div>
                            <h2 class="text-xl font-extrabold">
                                Datos del propietario
                            </h2>
                            <p
                                class="mt-1 text-sm text-[color:var(--home-muted)]"
                            >
                                Los campos con * son obligatorios.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    for="business_name"
                                    class="home-field-label"
                                >
                                    Nombre del negocio o cuenta *
                                </label>
                                <input
                                    id="business_name"
                                    v-model="form.business_name"
                                    class="home-control"
                                    placeholder="Ej. Jardín Los Encinos"
                                />
                                <p
                                    v-if="form.errors.business_name"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.business_name }}
                                </p>
                            </div>

                            <div>
                                <label for="role" class="home-field-label">
                                    Tu relación con la terraza *
                                </label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="home-control"
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
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="claimant_name"
                                    class="home-field-label"
                                >
                                    Nombre completo *
                                </label>
                                <input
                                    id="claimant_name"
                                    v-model="form.claimant_name"
                                    class="home-control"
                                    autocomplete="name"
                                />
                                <p
                                    v-if="form.errors.claimant_name"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.claimant_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="claimant_phone"
                                    class="home-field-label"
                                >
                                    Teléfono de contacto *
                                </label>
                                <input
                                    id="claimant_phone"
                                    v-model="form.claimant_phone"
                                    class="home-control"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    placeholder="33 1234 5678"
                                />
                                <p
                                    v-if="form.errors.claimant_phone"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.claimant_phone }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="whatsapp_phone"
                                    class="home-field-label"
                                >
                                    WhatsApp (opcional)
                                </label>
                                <input
                                    id="whatsapp_phone"
                                    v-model="form.whatsapp_phone"
                                    class="home-control"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    placeholder="33 1234 5678"
                                />
                                <p
                                    v-if="form.errors.whatsapp_phone"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.whatsapp_phone }}
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    for="target_team_id"
                                    class="home-field-label"
                                >
                                    ¿Dónde administrarás la terraza?
                                </label>
                                <select
                                    id="target_team_id"
                                    v-model="form.target_team_id"
                                    class="home-control"
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
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.target_team_id }}
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="message" class="home-field-label">
                                    ¿Cómo podemos verificar que eres el
                                    propietario? *
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    class="home-control min-h-28"
                                    rows="4"
                                    placeholder="Comparte RFC, predial, INE, redes sociales del negocio o cualquier dato que te identifique como responsable."
                                />
                                <p
                                    v-if="form.errors.message"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="evidence" class="home-field-label">
                                    Documento de apoyo (opcional)
                                </label>
                                <input
                                    id="evidence"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    class="home-control file:mr-3 file:rounded-md file:border-0 file:bg-[color:var(--home-bg-alt)] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-[color:var(--home-dark)]"
                                    @input="
                                        form.evidence =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <p
                                    class="mt-1 text-xs text-[color:var(--home-muted)]"
                                >
                                    PDF, JPG, PNG o WebP. Máximo 10 MB. Solo lo
                                    verá el equipo de plataforma.
                                </p>
                                <p
                                    v-if="form.errors.evidence"
                                    class="mt-1 text-sm text-red-700"
                                >
                                    {{ form.errors.evidence }}
                                </p>
                            </div>
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
                            class="mt-6 flex items-start gap-3 rounded-[0.75rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] p-4 text-sm text-[color:var(--home-secondary)]"
                        >
                            <input
                                v-model="form.accept_terms"
                                type="checkbox"
                                class="home-checkbox mt-0.5 shrink-0"
                            />
                            <span>
                                Declaro que soy el propietario o representante
                                autorizado de esta terraza y que la información
                                proporcionada es correcta.
                            </span>
                        </label>
                        <p
                            v-if="form.errors.accept_terms"
                            class="mt-1 text-sm text-red-700"
                        >
                            {{ form.errors.accept_terms }}
                        </p>

                        <p
                            v-if="listingError"
                            class="mt-4 rounded-[0.75rem] border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                        >
                            {{ listingError }}
                        </p>

                        <div
                            class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p
                                class="flex items-center gap-2 text-xs text-[color:var(--home-muted)]"
                            >
                                <BadgeCheck class="size-4" />
                                Tu correo de cuenta debe estar verificado.
                            </p>
                            <button
                                type="submit"
                                class="home-btn home-btn--primary w-full sm:w-auto"
                                :disabled="form.processing"
                            >
                                Enviar reclamación
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-24">
                    <div
                        class="overflow-hidden rounded-[1.125rem] border border-[color:var(--home-border)] bg-white"
                    >
                        <div class="home-media aspect-[4/3]">
                            <img
                                v-if="listing.cover_url"
                                :src="listing.cover_url"
                                :alt="listing.name"
                                class="absolute inset-0 h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-[color:var(--home-soft)]"
                            >
                                <span
                                    class="text-4xl font-extrabold text-[color:var(--home-primary-deep)]/60"
                                >
                                    {{ listing.name.charAt(0) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-5">
                            <span
                                class="home-chip border-[color:var(--home-primary)]/40 bg-[color:var(--home-primary)]/10 text-[color:var(--home-primary-deep)]"
                            >
                                Anuncio sin reclamar
                            </span>
                            <h2 class="mt-3 text-lg font-extrabold">
                                {{ listing.name }}
                            </h2>
                            <p
                                class="mt-1 text-sm text-[color:var(--home-secondary)]"
                            >
                                {{ location }}
                            </p>
                            <p
                                v-if="listing.address"
                                class="mt-1 text-xs text-[color:var(--home-muted)]"
                            >
                                {{ listing.address }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-white p-5"
                    >
                        <p class="home-eyebrow">Cómo funciona</p>
                        <ol class="mt-4 space-y-3">
                            <li
                                v-for="(step, index) in steps"
                                :key="step"
                                class="flex gap-3 text-sm text-[color:var(--home-secondary)]"
                            >
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--home-soft)] text-xs font-bold text-[color:var(--home-primary-deep)]"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span>{{ step }}</span>
                            </li>
                        </ol>
                    </div>

                    <div
                        class="rounded-[1.125rem] border border-[color:var(--home-border)] bg-[color:var(--home-bg-alt)] p-5 text-sm text-[color:var(--home-secondary)]"
                    >
                        <p class="flex items-center gap-2 font-semibold">
                            <ShieldCheck class="size-4" />
                            Revisión manual
                        </p>
                        <p class="mt-2 text-xs leading-relaxed">
                            No compartimos tu documento con el público. Si
                            necesitamos más datos, te contactaremos por correo o
                            teléfono.
                        </p>
                        <p
                            class="mt-3 flex items-center gap-2 text-xs font-medium text-[color:var(--home-primary-deep)]"
                        >
                            <Check class="size-3.5" />
                            Conservas la misma URL del anuncio.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
