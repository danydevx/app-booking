<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PaginationLinks from '@/components/PaginationLinks.vue';

type SubscriptionRow = {
    id: number;
    name: string;
    slug: string;
    owner_email: string | null;
    plan: string;
    plan_slug: string;
    status: string;
    stripe_status: string | null;
    ends_at: string | null;
    trial_ends_at: string | null;
    started_at: string | null;
    stripe_dashboard_url: string | null;
    account_url: string;
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
    accounts: Paginated<SubscriptionRow>;
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
        { preserveState: true, replace: true },
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
    <Head title="Suscripciones · Plataforma" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Suscripciones</h1>
                <p class="text-sm text-muted-foreground">
                    Vista de solo lectura. Los cambios se hacen en Stripe.
                </p>
            </div>

            <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="submit">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por cuenta, correo o Stripe ID…"
                    aria-label="Buscar suscripción"
                />
                <Button type="submit" variant="outline">Buscar</Button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3">Plan</th>
                        <th class="px-4 py-3">Estado interno</th>
                        <th class="px-4 py-3">Stripe</th>
                        <th class="px-4 py-3">Termina</th>
                        <th class="px-4 py-3">Inicio</th>
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
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(account.status)">
                                {{ account.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{ account.stripe_status ?? '—' }}
                        </td>
                        <td class="px-4 py-3">{{ account.ends_at ?? '—' }}</td>
                        <td class="px-4 py-3">{{ account.started_at ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="account.account_url">Cuenta</Link>
                                </Button>
                                <a
                                    v-if="account.stripe_dashboard_url"
                                    :href="account.stripe_dashboard_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-xs text-primary underline"
                                >
                                    Stripe
                                    <ExternalLink class="size-3" />
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="accounts.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No se encontraron suscripciones.
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
