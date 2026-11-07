<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified: boolean;
    is_superadmin: boolean;
    is_suspended: boolean;
    teams_count: number;
    owned_teams_count: number;
    current_team: { id: number; name: string; slug: string } | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { q: string };
    urls: {
        index: string;
        dashboard: string;
    };
}>();

const search = ref(props.filters.q ?? '');

function submit(): void {
    router.get(
        props.urls.index,
        { q: search.value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    );
}
</script>

<template>
    <Head title="Usuarios · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Usuarios registrados</h1>
                <p class="text-sm text-muted-foreground">
                    Busca por nombre, correo o cuenta. Desde la ficha puedes
                    suspender o restaurar el acceso.
                </p>
            </div>

            <form
                class="flex w-full max-w-md items-center gap-2"
                @submit.prevent="submit"
            >
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar usuario…"
                    aria-label="Buscar usuario"
                />
                <Button type="submit" variant="outline">Buscar</Button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead
                    class="bg-muted/50 text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Cuenta actual</th>
                        <th class="px-4 py-3">Equipos</th>
                        <th class="px-4 py-3">Alta</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ user.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </td>
                        <td class="space-x-1 px-4 py-3">
                            <Badge
                                v-if="user.is_superadmin"
                                variant="secondary"
                            >
                                Superadmin
                            </Badge>
                            <Badge
                                v-if="!user.email_verified"
                                variant="outline"
                            >
                                Sin verificar
                            </Badge>
                            <Badge
                                v-if="user.is_suspended"
                                variant="destructive"
                            >
                                Suspendido
                            </Badge>
                            <Badge
                                v-if="
                                    user.email_verified &&
                                    !user.is_superadmin &&
                                    !user.is_suspended
                                "
                                variant="default"
                            >
                                Activo
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <template v-if="user.current_team">
                                <p class="font-medium">
                                    {{ user.current_team.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    /{{ user.current_team.slug }}
                                </p>
                            </template>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ user.teams_count }}
                            <span class="text-xs text-muted-foreground">
                                ({{ user.owned_teams_count }} propios)
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ user.created_at ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`${urls.index}/${user.id}`">
                                    Ver
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No se encontraron usuarios.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ users.from ?? 0 }}–{{ users.to ?? 0 }} de
                {{ users.total }}
            </p>
            <PaginationLinks :links="users.links" />
        </div>
    </div>
</template>
