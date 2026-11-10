<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, CreditCard, ShieldAlert, X } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type PlanPayload = {
    slug: string;
    name: string;
    description: string;
    interval: string;
    active: boolean;
    is_free: boolean;
    price_configured: boolean;
    limits: Record<string, number>;
    features: Record<string, boolean>;
    is_current: boolean;
    checkout_url: string | null;
    usage: {
        listings: number;
        customers: number;
    } | null;
};

type InvoicePayload = {
    id: string;
    date: string;
    total: string;
    status: string;
    url: string | null;
};

const props = defineProps<{
    team: { name: string; slug: string };
    plan: PlanPayload & {
        usage: { listings: number; customers: number };
    };
    plans: PlanPayload[];
    subscription: {
        status: string;
        stripe_status: string | null;
        ends_at: string | null;
        trial_ends_at: string | null;
        has_customer: boolean;
        stripe_configured: boolean;
    };
    labels: {
        limits: Record<string, string>;
        features: Record<string, string>;
    };
    invoices: InvoicePayload[];
    checkoutResult: string;
    urls: {
        index: string;
        checkout: string;
        portal: string;
    };
}>();

defineOptions({
    layout: (props: { team: { slug: string } }) => ({
        breadcrumbs: [
            {
                title: 'Facturación',
                href: `/${props.team.slug}/dashboard`,
            },
        ],
    }),
});

const statusLabels: Record<string, string> = {
    free: 'Plan Gratis',
    active: 'Suscripción activa',
    trialing: 'Periodo de prueba',
    canceling: 'Cancelación programada',
    past_due_grace: 'Pago pendiente · periodo de gracia',
    past_due_locked: 'Pago pendiente · plan Gratis aplicado',
    canceled: 'Suscripción cancelada',
};

const invoiceStatusLabels: Record<string, string> = {
    paid: 'Pagada',
    open: 'Pendiente de pago',
    void: 'Anulada',
    draft: 'Borrador',
    uncollectible: 'No cobrable',
};

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

function checkout(plan: PlanPayload): void {
    if (!plan.price_configured || !props.subscription.stripe_configured) {
        return;
    }

    if (
        !window.confirm(
            `¿Cambiar la cuenta al plan ${plan.name}? Se abrirá Stripe Checkout.`,
        )
    ) {
        return;
    }

    router.post(
        props.urls.checkout,
        { plan: plan.slug },
        { preserveScroll: true },
    );
}

function openPortal(): void {
    router.post(props.urls.portal, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Facturación" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Facturación</h1>
                <p class="text-sm text-muted-foreground">
                    Plan, límites y pagos de {{ team.name }}.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Badge :variant="statusVariant(subscription.status)">
                    {{
                        statusLabels[subscription.status] ?? subscription.status
                    }}
                </Badge>
                <Button
                    v-if="
                        subscription.has_customer &&
                        subscription.stripe_configured
                    "
                    variant="outline"
                    @click="openPortal"
                >
                    <CreditCard />
                    Administrar facturación
                </Button>
            </div>
        </div>

        <div
            v-if="checkoutResult === 'success'"
            class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
        >
            Stripe recibió el pago. El plan se actualizará cuando llegue el
            webhook de confirmación; recargar esta página no activa nada por sí
            solo.
        </div>

        <div
            v-else-if="checkoutResult === 'cancelled'"
            class="rounded-xl border bg-muted/40 p-4 text-sm text-muted-foreground"
        >
            El pago fue cancelado. Tu plan actual no cambió.
        </div>

        <div
            v-if="!subscription.stripe_configured"
            class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
        >
            <ShieldAlert class="mt-0.5 size-4 shrink-0" />
            <div>
                <p class="font-medium">Stripe no está configurado</p>
                <p>
                    Agrega llaves de prueba en `.env` y configura
                    `STRIPE_PRICE_PRO` para habilitar Checkout. No se pueden
                    hacer cobros reales desde este estado.
                </p>
            </div>
        </div>

        <div
            v-if="subscription.status === 'canceling'"
            class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
        >
            La suscripción seguirá activa hasta
            {{ subscription.ends_at }}. Después de esa fecha la cuenta vuelve al
            plan Gratis.
        </div>

        <div
            v-else-if="subscription.status.startsWith('past_due')"
            class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
        >
            Stripe reportó un pago pendiente. Revisa el método de pago en el
            portal de facturación.
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Plan actual</CardTitle>
                    <CardDescription>{{ plan.description }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-3xl font-semibold">{{ plan.name }}</p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">Terrazas</span>
                            <span class="font-medium">
                                {{ plan.usage.listings }} /
                                {{ plan.limits.listings }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">Clientes</span>
                            <span class="font-medium">
                                {{ plan.usage.customers }}
                                <span
                                    v-if="!plan.features.customers"
                                    class="text-muted-foreground"
                                >
                                    · solo registro
                                </span>
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Estado de Stripe</CardTitle>
                    <CardDescription>
                        La suscripción se confirma por webhook, no por la
                        pantalla de éxito.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Estado</span>
                        <span class="font-medium">
                            {{
                                subscription.stripe_status ?? 'Sin suscripción'
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground"
                            >Cliente Stripe</span
                        >
                        <span class="font-medium">
                            {{
                                subscription.has_customer
                                    ? 'Vinculado'
                                    : 'No creado'
                            }}
                        </span>
                    </div>
                    <div
                        v-if="subscription.trial_ends_at"
                        class="flex justify-between gap-3"
                    >
                        <span class="text-muted-foreground">Fin de prueba</span>
                        <span class="font-medium">
                            {{ subscription.trial_ends_at }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card
                v-for="candidate in plans"
                :key="candidate.slug"
                :class="candidate.is_current ? 'border-primary' : ''"
            >
                <CardHeader>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle>{{ candidate.name }}</CardTitle>
                            <CardDescription>
                                {{ candidate.description }}
                            </CardDescription>
                        </div>
                        <Badge v-if="candidate.is_current" variant="default">
                            Actual
                        </Badge>
                        <Badge v-else variant="outline">
                            {{
                                candidate.interval === 'month'
                                    ? 'Mensual'
                                    : candidate.interval
                            }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="space-y-2 text-sm">
                        <div
                            v-for="(limit, key) in candidate.limits"
                            :key="key"
                            class="flex items-center justify-between gap-3"
                        >
                            <span class="text-muted-foreground">
                                {{ labels.limits[key] ?? key }}
                            </span>
                            <span class="font-medium">{{ limit }}</span>
                        </div>
                    </div>

                    <div class="space-y-2 border-t pt-4 text-sm">
                        <div
                            v-for="(enabled, key) in candidate.features"
                            :key="key"
                            class="flex items-center gap-2"
                        >
                            <Check
                                v-if="enabled"
                                class="size-4 text-emerald-600"
                            />
                            <X v-else class="size-4 text-muted-foreground" />
                            <span
                                :class="
                                    enabled
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ labels.features[key] ?? key }}
                            </span>
                        </div>
                    </div>

                    <Button
                        v-if="!candidate.is_current && !candidate.is_free"
                        class="w-full"
                        :disabled="
                            !candidate.price_configured ||
                            !subscription.stripe_configured
                        "
                        @click="checkout(candidate)"
                    >
                        Cambiar a {{ candidate.name }}
                    </Button>
                    <p
                        v-else-if="candidate.is_current"
                        class="text-sm text-muted-foreground"
                    >
                        Este es el plan vigente de la cuenta.
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Plan base incluido.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card v-if="invoices.length">
            <CardHeader>
                <CardTitle>Facturas</CardTitle>
                <CardDescription>
                    Últimos movimientos de Stripe para esta cuenta.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="divide-y">
                    <div
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"
                    >
                        <div>
                            <p class="font-medium">{{ invoice.date }}</p>
                            <p class="text-muted-foreground">
                                {{
                                    invoiceStatusLabels[invoice.status] ??
                                    invoice.status
                                }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-medium">{{ invoice.total }}</span>
                            <a
                                v-if="invoice.url"
                                :href="invoice.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm underline underline-offset-4"
                            >
                                Ver factura
                            </a>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <p class="text-xs text-muted-foreground">
            Los métodos de pago y las cancelaciones se administran en el portal
            de Stripe. Los anticipos de reservas siguen fuera de Stripe y se
            registran manualmente; esta página no procesa pagos de renter.
        </p>
    </div>
</template>
