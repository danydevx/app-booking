<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PaginationLinks from '@/components/PaginationLinks.vue';

type AccountRow = {
    id: number;
    name: string;
    slug: string;
    owner_name: string | null;
    owner_email: string | null;
    plan: string;
    plan_slug: string;
    status: string;
    stripe_status: string | null;
    listings_count: number;
    members_count: number;
    is_suspended: boolean;
    suspended_at: string | null;
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
    accounts: Paginated<AccountRow>;
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

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'active' || status === 'trialing') {
        return 'default';
    }

    if (status.startsWith('past_due')) {
        return 'destructive';
    }

    if (status === 'canceling') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head title="Cuentas · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Cuentas</h1>
                <p class="text-sm text-muted-foreground">
                    Busca por nombre, correo del propietario o cliente de
                    Stripe.
                </p>
            </div>

            <form
                class="flex w-full max-w-md items-center gap-2"
                @submit.prevent="submit"
            >
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar cuenta…"
                    aria-label="Buscar cuenta"
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
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3">Plan</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Terrazas</th>
                        <th class="px-4 py-3">Miembros</th>
                        <th class="px-4 py-3">Alta</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="account in accounts.data" :key="account.id">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ account.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ account.owner_email ?? 'Sin propietario' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">{{ account.plan }}</td>
                        <td class="space-y-1 px-4 py-3">
                            <Badge :variant="statusVariant(account.status)">
                                {{ account.status }}
                            </Badge>
                            <Badge
                                v-if="account.is_suspended"
                                variant="destructive"
                                class="ml-1"
                            >
                                Suspendida
                            </Badge>
                        </td>
                        <td class="px-4 py-3">{{ account.listings_count }}</td>
                        <td class="px-4 py-3">{{ account.members_count }}</td>
                        <td class="px-4 py-3">
                            {{ account.created_at ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`${urls.index}/${account.slug}`">
                                    Ver
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="accounts.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No se encontraron cuentas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ accounts.from ?? 0 }}–{{ accounts.to ?? 0 }} de
                {{ accounts.total }}
            </p>
            <PaginationLinks :links="accounts.links" />
        </div>
    </div>
</template>
