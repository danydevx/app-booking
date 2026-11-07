<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ModerationForm from '../../components/ModerationForm.vue';

type TeamRow = {
    id: number;
    name: string;
    slug: string;
    role: string;
    role_label: string;
    is_current: boolean;
    platform_url: string;
};

type ActivityRow = {
    id: number;
    description: string;
    subject_type: string;
    subject_name: string | null;
    causer: string | null;
    properties: Record<string, unknown>;
    created_at: string | null;
};

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        email_verified: boolean;
        is_superadmin: boolean;
        is_suspended: boolean;
        suspended_at: string | null;
        suspension_reason: string | null;
        created_at: string | null;
    };
    current_team: { id: number; name: string; slug: string } | null;
    teams: TeamRow[];
    activities: ActivityRow[];
    can: {
        suspend: boolean;
        manage_roles: boolean;
        notify: boolean;
        is_self: boolean;
        is_last_superadmin: boolean;
    };
    urls: {
        index: string;
        suspend: string;
        restore: string;
        resend_verification: string;
        send_password_reset: string;
        grant_superadmin: string;
        revoke_superadmin: string;
    };
}>();

const roleForm = useForm({ user: '' });
const supportForm = useForm({ user: '' });

function grantSuperadmin(): void {
    if (
        !window.confirm(`¿Otorgar acceso de superadmin a ${props.user.email}?`)
    ) {
        return;
    }

    roleForm.post(props.urls.grant_superadmin, { preserveScroll: true });
}

function revokeSuperadmin(): void {
    if (
        !window.confirm(
            `¿Revocar el acceso de superadmin de ${props.user.email}?`,
        )
    ) {
        return;
    }

    roleForm.post(props.urls.revoke_superadmin, { preserveScroll: true });
}

function resendVerification(): void {
    if (
        !window.confirm(
            `¿Reenviar el correo de verificación a ${props.user.email}?`,
        )
    ) {
        return;
    }

    supportForm.post(props.urls.resend_verification, {
        preserveScroll: true,
    });
}

function sendPasswordReset(): void {
    if (
        !window.confirm(
            `¿Enviar un enlace de restablecimiento de contraseña a ${props.user.email}?`,
        )
    ) {
        return;
    }

    supportForm.post(props.urls.send_password_reset, {
        preserveScroll: true,
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function reasonOf(activity: ActivityRow): string {
    const reason = activity.properties.reason;

    return typeof reason === 'string' ? reason : '';
}
</script>

<template>
    <Head :title="`${user.name} · Plataforma`" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <Link
                    :href="urls.index"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Usuarios
                </Link>
                <h1 class="mt-1 text-2xl font-semibold">{{ user.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ user.email }} · Alta {{ formatDate(user.created_at) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge :variant="user.is_suspended ? 'destructive' : 'default'">
                    {{ user.is_suspended ? 'Suspendido' : 'Activo' }}
                </Badge>
                <Badge v-if="user.is_superadmin" variant="secondary">
                    Superadmin
                </Badge>
                <Badge v-if="!user.email_verified" variant="outline">
                    Sin verificar
                </Badge>
            </div>
        </div>

        <div
            v-if="user.is_suspended"
            class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
        >
            <p class="font-medium">Usuario suspendido</p>
            <p class="text-muted-foreground">
                {{ user.suspension_reason }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Suspendido el {{ formatDate(user.suspended_at) }}. No puede
                iniciar sesión ni acceder a la aplicación.
            </p>
        </div>

        <ModerationForm
            v-if="can.suspend && !user.is_suspended"
            :action="urls.suspend"
            title="Suspender usuario"
            description="Bloquea el acceso del usuario a la aplicación. Sus cuentas y datos no se eliminan."
            submit-label="Suspender"
            destructive
        />

        <ModerationForm
            v-if="can.suspend && user.is_suspended"
            :action="urls.restore"
            title="Restaurar usuario"
            description="Devuelve el acceso del usuario a la aplicación."
            submit-label="Restaurar"
        />

        <Card v-if="can.notify">
            <CardHeader>
                <CardTitle>Soporte de acceso</CardTitle>
                <CardDescription>
                    Reenvía correos de verificación o restablecimiento sin
                    cambiar la contraseña del usuario.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="!user.email_verified"
                        type="button"
                        variant="outline"
                        :disabled="supportForm.processing"
                        @click="resendVerification"
                    >
                        Reenviar verificación
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="supportForm.processing"
                        @click="sendPasswordReset"
                    >
                        Enviar restablecimiento de contraseña
                    </Button>
                </div>
                <p
                    v-if="supportForm.errors.user"
                    class="text-sm text-destructive"
                >
                    {{ supportForm.errors.user }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Acceso de plataforma</CardTitle>
                <CardDescription>
                    El superadmin puede revisar cuentas, terrazas, reportes y
                    moderación.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <p>
                    <span class="text-muted-foreground">Rol actual:</span>
                    {{ user.is_superadmin ? 'Superadmin' : 'Usuario normal' }}
                </p>

                <p v-if="can.is_self" class="text-muted-foreground">
                    No puedes cambiar tu propio acceso de plataforma.
                </p>
                <p
                    v-else-if="can.is_last_superadmin"
                    class="text-muted-foreground"
                >
                    Es el último superadmin; no se puede revocar.
                </p>
                <Button
                    v-else-if="can.manage_roles && user.is_superadmin"
                    type="button"
                    variant="destructive"
                    :disabled="roleForm.processing"
                    @click="revokeSuperadmin"
                >
                    Revocar superadmin
                </Button>
                <Button
                    v-else-if="can.manage_roles"
                    type="button"
                    :disabled="roleForm.processing"
                    @click="grantSuperadmin"
                >
                    Otorgar superadmin
                </Button>

                <p v-if="roleForm.errors.user" class="text-sm text-destructive">
                    {{ roleForm.errors.user }}
                </p>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Datos del usuario</CardTitle>
                    <CardDescription>Solo lectura.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p>
                        <span class="text-muted-foreground">Correo:</span>
                        {{ user.email }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">Verificado:</span>
                        {{ user.email_verified ? 'Sí' : 'No' }}
                    </p>
                    <p>
                        <span class="text-muted-foreground">Alta:</span>
                        {{ formatDate(user.created_at) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Cuenta actual</CardTitle>
                    <CardDescription>Equipo seleccionado.</CardDescription>
                </CardHeader>
                <CardContent class="text-sm">
                    <template v-if="current_team">
                        <p class="font-medium">{{ current_team.name }}</p>
                        <p class="text-muted-foreground">
                            /{{ current_team.slug }}
                        </p>
                    </template>
                    <p v-else class="text-muted-foreground">
                        Sin cuenta actual.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Cuentas y roles</CardTitle>
                <CardDescription>
                    Membresías del usuario en la plataforma.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="teams.length > 0" class="divide-y">
                    <div
                        v-for="team in teams"
                        :key="team.id"
                        class="flex flex-wrap items-center justify-between gap-3 py-3"
                    >
                        <div>
                            <p class="font-medium">
                                {{ team.name }}
                                <Badge
                                    v-if="team.is_current"
                                    variant="outline"
                                    class="ml-1"
                                >
                                    Actual
                                </Badge>
                            </p>
                            <p class="text-xs text-muted-foreground">
                                /{{ team.slug }} · {{ team.role_label }}
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="team.platform_url">Ver cuenta</Link>
                        </Button>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    El usuario no pertenece a ninguna cuenta.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Actividad de moderación</CardTitle>
                <CardDescription>
                    Últimos cambios de acceso del usuario.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="activities.length > 0" class="divide-y">
                    <div
                        v-for="activity in activities"
                        :key="activity.id"
                        class="py-3 text-sm"
                    >
                        <p class="font-medium">
                            {{ activity.description }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(activity.created_at) }}
                            <span v-if="activity.causer">
                                · {{ activity.causer }}</span
                            >
                        </p>
                        <p
                            v-if="reasonOf(activity)"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Motivo: {{ reasonOf(activity) }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Sin actividad de moderación.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
