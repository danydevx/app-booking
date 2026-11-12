<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    CustomerQuoteCard,
    QuotationCustomerOption,
    QuotationListing,
} from '../../types';

type ItemDraft = {
    uid: string;
    id?: number;
    type: 'rental' | 'service' | 'package' | 'product' | 'custom';
    description: string;
    details: string;
    unit: 'hour' | 'day' | 'event' | 'service' | 'piece' | 'person';
    quantity: number;
    unit_price: number;
    auto_rental: boolean;
};

let itemSequence = 0;

function createItemUid(): string {
    itemSequence += 1;

    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return `quote-item-${crypto.randomUUID()}`;
    }

    return `quote-item-${Date.now()}-${itemSequence}`;
}

function initialItems(quote: CustomerQuoteCard | null): ItemDraft[] {
    let rentalAssigned = false;

    return (quote?.items ?? []).map((item): ItemDraft => {
        const autoRental = item.type === 'rental' && !rentalAssigned;

        if (autoRental) {
            rentalAssigned = true;
        }

        return {
            // Los IDs persistidos se usan como identidad estable para evitar
            // diferencias entre SSR e hidratación del cliente.
            uid: `quote-item-${item.id}`,
            id: item.id,
            type: item.type,
            description: item.description,
            details: item.details ?? '',
            unit: item.unit as ItemDraft['unit'],
            quantity: item.quantity,
            unit_price: item.unit_price_cents / 100,
            auto_rental: autoRental,
        };
    });
}

type ListingOption = QuotationListing;

const props = defineProps<{
    team: { name: string; slug: string };
    defaults: {
        currency: string;
        tax_rate_bps: number;
        valid_days: number;
        rental_mode: 'hour' | 'day' | 'fixed';
        tax_mode: 'none' | 'added' | 'included';
        discount_type: 'none' | 'fixed' | 'percent';
    };
    listings: ListingOption[];
    customers: QuotationCustomerOption[];
    quote: CustomerQuoteCard | null;
    prefill: {
        listing_id: number | null;
        customer_id: number | null;
        inquiry_id: number | null;
        start_date: string | null;
        end_date: string | null;
        event_type: string | null;
        guests: number | null;
    } | null;
    urls: {
        index: string;
        store: string;
        update?: string;
        bookings: string;
        customers: string;
        billing: string;
    };
}>();

defineOptions({
    layout: (props: { urls: { bookings: string; index: string } }) => ({
        breadcrumbs: [
            { title: 'Solicitudes', href: props.urls.bookings },
            { title: 'Cotizaciones', href: props.urls.index },
            { title: 'Editor' },
        ],
    }),
});

const today = new Date();
const defaultStart = new Date(today.getTime() + 7 * 86_400_000)
    .toISOString()
    .slice(0, 10);

function toDateInput(value: string | null | undefined): string {
    return value ? value.slice(0, 10) : defaultStart;
}

const form = useForm({
    intent: 'draft' as 'draft' | 'preview' | 'issue',
    listing_id: props.quote?.listing.id ?? props.prefill?.listing_id ?? '',
    customer_id: props.quote?.customer.id ?? props.prefill?.customer_id ?? '',
    new_customer: {
        name: '',
        email: '',
        phone: '',
        notes: '',
    },
    converted_inquiry_id:
        props.quote?.converted_inquiry_id ?? props.prefill?.inquiry_id ?? null,
    start_date: toDateInput(
        props.quote?.start_date ?? props.prefill?.start_date,
    ),
    end_date: toDateInput(
        props.quote?.end_date ??
            props.prefill?.end_date ??
            props.prefill?.start_date,
    ),
    rental_mode:
        props.quote?.rental_mode ?? props.defaults.rental_mode ?? 'fixed',
    start_time: props.quote?.start_time ?? '12:00',
    end_time: props.quote?.end_time ?? '23:00',
    billing_quantity: props.quote?.billing_quantity ?? 1,
    event_type: props.quote?.event_type ?? props.prefill?.event_type ?? '',
    guests: props.quote?.guests ?? props.prefill?.guests ?? '',
    tax_mode: props.quote?.tax_mode ?? props.defaults.tax_mode ?? 'none',
    discount_type:
        props.quote?.discount_type ?? props.defaults.discount_type ?? 'none',
    discount_input:
        props.quote && props.quote.discount_type === 'percent'
            ? props.quote.discount_value / 100
            : props.quote && props.quote.discount_type === 'fixed'
              ? props.quote.discount_value / 100
              : 0,
    deposit: props.quote ? props.quote.deposit_cents / 100 : 0,
    notes: props.quote?.notes ?? '',
    terms: props.quote?.terms ?? '',
    expires_at: props.quote?.expires_at
        ? props.quote.expires_at.slice(0, 10)
        : '',
    items: initialItems(props.quote),
});

const customerMode = ref<'existing' | 'new'>(
    props.quote || props.prefill?.customer_id ? 'existing' : 'existing',
);

const selectedListing = computed<ListingOption | null>(
    () =>
        props.listings.find(
            (listing) => String(listing.id) === String(form.listing_id),
        ) ?? null,
);

const customerOptions = computed(() => props.customers);

const availableServices = computed(
    () =>
        selectedListing.value?.services.filter(
            (service) => !service.is_included,
        ) ?? [],
);

watch(
    () => form.customer_id,
    (value) => {
        if (value !== '' && value !== null) {
            customerMode.value = 'existing';
        }
    },
);

watch(
    () => form.listing_id,
    () => {
        if (form.items.length === 0 && selectedListing.value) {
            form.start_date = form.start_date || defaultStart;
            form.end_date = form.end_date || form.start_date;
        }
    },
);

const dateDayCount = computed(() => {
    if (!form.start_date || !form.end_date) {
        return 1;
    }

    const start = new Date(`${form.start_date}T00:00:00`);
    const end = new Date(`${form.end_date}T00:00:00`);
    const days = Math.round((end.getTime() - start.getTime()) / 86_400_000) + 1;

    return Math.max(1, days);
});

const hourCount = computed(() => {
    if (!form.start_time || !form.end_time) {
        return 1;
    }

    const [startHour, startMinute] = form.start_time.split(':').map(Number);
    const [endHour, endMinute] = form.end_time.split(':').map(Number);
    let minutes = endHour * 60 + endMinute - (startHour * 60 + startMinute);

    if (minutes <= 0) {
        minutes += 24 * 60;
    }

    return Math.max(1, Math.ceil(minutes / 60));
});

watch(
    () => [
        form.rental_mode,
        form.start_date,
        form.end_date,
        form.start_time,
        form.end_time,
    ],
    () => {
        if (form.rental_mode === 'hour') {
            form.billing_quantity = hourCount.value;
        } else if (form.rental_mode === 'day') {
            form.billing_quantity = dateDayCount.value;
        } else {
            form.billing_quantity = 1;
        }
    },
    { immediate: true },
);

const itemSubtotalCents = computed(() =>
    form.items.reduce(
        (total, item) =>
            total +
            Math.round(
                Math.max(0, Number(item.quantity) || 0) *
                    Math.max(0, Number(item.unit_price) || 0) *
                    100,
            ),
        0,
    ),
);

const discountCents = computed(() => {
    if (form.discount_type === 'percent') {
        const bps = Math.min(
            Math.max(0, Number(form.discount_input) || 0) * 100,
            10_000,
        );

        return Math.round((itemSubtotalCents.value * bps) / 10_000);
    }

    if (form.discount_type === 'fixed') {
        return Math.min(
            Math.max(0, Math.round((Number(form.discount_input) || 0) * 100)),
            itemSubtotalCents.value,
        );
    }

    return 0;
});

const taxBaseCents = computed(() =>
    Math.max(0, itemSubtotalCents.value - discountCents.value),
);

const taxRateBps = computed(
    () => props.quote?.tax_rate_bps ?? props.defaults.tax_rate_bps,
);

const taxCents = computed(() => {
    const rate = form.tax_mode === 'none' ? 0 : taxRateBps.value;

    if (rate === 0) {
        return 0;
    }

    if (form.tax_mode === 'included') {
        return Math.round((taxBaseCents.value * rate) / (10_000 + rate));
    }

    return Math.round((taxBaseCents.value * rate) / 10_000);
});

const totalCents = computed(() => {
    if (form.tax_mode === 'added') {
        return taxBaseCents.value + taxCents.value;
    }

    return taxBaseCents.value;
});

function money(cents: number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: props.defaults.currency,
    }).format(cents / 100);
}

function addConcept(): void {
    form.items.push({
        uid: createItemUid(),
        type: 'service',
        description: '',
        details: '',
        unit: 'service',
        quantity: 1,
        unit_price: 0,
        auto_rental: false,
    });
}

function rentalUnit(): ItemDraft['unit'] {
    if (form.rental_mode === 'hour') {
        return 'hour';
    }

    if (form.rental_mode === 'day') {
        return 'day';
    }

    return 'event';
}

function rentalQuantity(): number {
    if (form.rental_mode === 'fixed') {
        return 1;
    }

    return Math.max(1, Number(form.billing_quantity) || 1);
}

function rentalDescription(listing: ListingOption): string {
    return `Renta de ${listing.name}`;
}

function rentalDetails(): string {
    const quantity = rentalQuantity();

    if (form.rental_mode === 'hour') {
        return `Renta por hora · ${form.start_date} ${form.start_time} a ${form.end_time} · ${quantity} hora(s)`;
    }

    if (form.rental_mode === 'day') {
        return `Renta por día · ${form.start_date} al ${form.end_date} · ${quantity} día(s)`;
    }

    return `Precio fijo por evento · ${form.start_date}`;
}

function autoRentalItem(): ItemDraft | undefined {
    return form.items.find((item) => item.auto_rental);
}

const hasAutoRental = computed(() =>
    form.items.some((item) => item.auto_rental),
);

function addRentalItem(): void {
    const listing = selectedListing.value;

    if (!listing) {
        return;
    }

    const description = rentalDescription(listing);
    const details = rentalDetails();
    const unit = rentalUnit();
    const quantity = rentalQuantity();
    const existing = autoRentalItem();

    if (existing) {
        // Nunca crear una segunda renta automática por doble clic o por volver
        // a pulsar la acción. Se conserva el precio ajustado manualmente.
        existing.type = 'rental';
        existing.description = description;
        existing.details = details;
        existing.unit = unit;
        existing.quantity = quantity;
        existing.auto_rental = true;

        return;
    }

    form.items.push({
        uid: createItemUid(),
        type: 'rental',
        description,
        details,
        unit,
        quantity,
        unit_price: (listing.price_from_cents ?? 0) / 100,
        auto_rental: true,
    });
}

function duplicateItem(index: number): void {
    const item = form.items[index];

    if (!item) {
        return;
    }

    form.items.splice(index + 1, 0, {
        ...item,
        uid: createItemUid(),
        id: undefined,
        description: item.description ? `${item.description} (copia)` : '',
        auto_rental: false,
    });
}

function onItemTypeChange(item: ItemDraft): void {
    // Cambiar el tipo no debe tocar el concepto escrito. Si la partida
    // automática deja de ser renta, se libera para poder crear otra.
    if (item.type !== 'rental') {
        item.auto_rental = false;
    }
}

function addService(serviceId: string): void {
    const listing = selectedListing.value;
    const service = availableServices.value.find(
        (item) => String(item.id) === String(serviceId),
    );

    if (!service) {
        return;
    }

    const unit =
        service.price_unit === 'hour'
            ? 'hour'
            : service.price_unit === 'person'
              ? 'person'
              : service.price_unit === 'night'
                ? 'day'
                : 'service';

    form.items.push({
        uid: createItemUid(),
        type: 'service',
        description: service.label,
        details: service.description ?? '',
        unit,
        quantity: 1,
        unit_price: service.price_cents / 100,
        auto_rental: false,
    });
}

function addPackage(packageId: string): void {
    const listing = selectedListing.value;
    const pack = listing?.packages.find(
        (item) => String(item.id) === String(packageId),
    );

    if (!pack) {
        return;
    }

    const included = pack.services.map((service) => service.label).join(', ');

    form.items.push({
        uid: createItemUid(),
        type: 'package',
        description: pack.name,
        details: [
            pack.description,
            included ? `Incluye: ${included}` : null,
            pack.conditions,
        ]
            .filter(Boolean)
            .join(' · '),
        unit: 'event',
        quantity: 1,
        unit_price: pack.price_cents / 100,
        auto_rental: false,
    });
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function moveItem(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= form.items.length) {
        return;
    }

    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);
}

function refreshBillingQuantity(): void {
    if (form.rental_mode === 'hour') {
        form.billing_quantity = hourCount.value;
    } else if (form.rental_mode === 'day') {
        form.billing_quantity = dateDayCount.value;
    } else {
        form.billing_quantity = 1;
    }

    const rental = autoRentalItem();
    const listing = selectedListing.value;

    if (!rental || !listing) {
        return;
    }

    // Solo se recalcula la partida automática de renta. Los servicios y
    // conceptos personalizados permanecen intactos, igual que el precio
    // unitario ajustado por el propietario.
    rental.type = 'rental';
    rental.description = rentalDescription(listing);
    rental.details = rentalDetails();
    rental.unit = rentalUnit();
    rental.quantity = rentalQuantity();
}

function submit(intent: 'draft' | 'preview' | 'issue'): void {
    form.intent = intent;
    form.transform((data) => ({
        ...data,
        customer_id:
            customerMode.value === 'existing' ? data.customer_id || null : null,
        new_customer:
            customerMode.value === 'new'
                ? data.new_customer
                : { name: '', email: '', phone: '', notes: '' },
        discount_value:
            data.discount_type === 'percent'
                ? Math.round((Number(data.discount_input) || 0) * 100)
                : data.discount_type === 'fixed'
                  ? Math.round((Number(data.discount_input) || 0) * 100)
                  : 0,
        deposit_cents: Math.round((Number(data.deposit) || 0) * 100),
        items: data.items.map((item, index) => ({
            type: item.type,
            description: item.description,
            details: item.details || null,
            unit: item.unit,
            quantity: Number(item.quantity) || 1,
            unit_price_cents: Math.round((Number(item.unit_price) || 0) * 100),
            sort_order: index,
        })),
    }));

    if (props.quote && props.urls.update) {
        form.patch(props.urls.update, { preserveScroll: true });
    } else {
        form.post(props.urls.store);
    }
}

function duplicate(): void {
    if (!props.quote) {
        return;
    }

    router.post(props.quote.duplicate_url);
}

function cancelQuote(): void {
    if (!props.quote) {
        return;
    }

    if (
        !window.confirm(
            '¿Cancelar esta cotización? Esta acción no se puede deshacer.',
        )
    ) {
        return;
    }

    router.post(props.quote.cancel_url);
}

const statusVariant = computed<
    'default' | 'secondary' | 'destructive' | 'outline'
>(() => {
    if (props.quote?.status === 'accepted') {
        return 'default';
    }

    if (
        props.quote?.status === 'declined' ||
        props.quote?.status === 'expired' ||
        props.quote?.status === 'cancelled'
    ) {
        return 'destructive';
    }

    if (props.quote?.status === 'sent') {
        return 'secondary';
    }

    return 'outline';
});

const isLocked = computed(() => Boolean(props.quote && !props.quote.can_edit));

function addServiceFromSelect(event: Event): void {
    const select = event.target as HTMLSelectElement;
    addService(select.value);
    select.value = '';
}

function addPackageFromSelect(event: Event): void {
    const select = event.target as HTMLSelectElement;
    addPackage(select.value);
    select.value = '';
}

function itemError(index: number, field: string): string | undefined {
    return fieldError(`items.${index}.${field}`);
}

function fieldError(field: string): string | undefined {
    const errors = form.errors as Record<string, string | undefined>;

    return errors[field];
}

const taxRateLabel = computed(() => `${(taxRateBps.value / 100).toFixed(2)}%`);
</script>

<template>
    <Head :title="quote ? `Editar ${quote.folio}` : 'Nueva cotización'" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">
                        {{
                            quote
                                ? `Cotización ${quote.folio}`
                                : 'Nueva cotización'
                        }}
                    </h1>
                    <Badge v-if="quote" :variant="statusVariant">
                        {{ quote.status_label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Documento comercial en PDF. No es una factura fiscal ni un
                    CFDI.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="urls.index">Volver</Link>
                </Button>
                <Button v-if="quote?.pdf_url" variant="outline" as-child>
                    <a :href="quote.pdf_url" target="_blank" rel="noopener">
                        Descargar PDF
                    </a>
                </Button>
                <Button v-if="quote" variant="outline" @click="duplicate">
                    Duplicar
                </Button>
            </div>
        </div>

        <div
            v-if="isLocked"
            class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"
        >
            Esta cotización fue emitida y ya no se puede modificar. Duplica la
            cotización para preparar una nueva versión sin alterar el documento
            original.
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            <fieldset :disabled="isLocked" class="space-y-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Cliente</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                customerMode === 'existing'
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="customerMode = 'existing'"
                        >
                            Cliente existente
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                customerMode === 'new' ? 'default' : 'outline'
                            "
                            @click="customerMode = 'new'"
                        >
                            Nuevo cliente
                        </Button>
                    </div>

                    <div
                        v-if="customerMode === 'existing'"
                        class="mt-4 grid gap-2"
                    >
                        <Label for="customer">Selecciona un cliente</Label>
                        <select
                            id="customer"
                            v-model="form.customer_id"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Selecciona…</option>
                            <option
                                v-for="customer in customerOptions"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.name }} · {{ customer.email }}
                            </option>
                        </select>
                        <InputError :message="form.errors.customer_id" />
                        <p class="text-xs text-muted-foreground">
                            ¿No existe?
                            <Link :href="urls.customers" class="underline">
                                Créalo en el CRM
                            </Link>
                            o usa la pestaña «Nuevo cliente».
                        </p>
                    </div>

                    <div v-else class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="new-customer-name"
                                >Nombre o empresa</Label
                            >
                            <Input
                                id="new-customer-name"
                                v-model="form.new_customer.name"
                            />
                            <InputError
                                :message="form.errors['new_customer.name']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="new-customer-email"
                                >Correo electrónico</Label
                            >
                            <Input
                                id="new-customer-email"
                                type="email"
                                v-model="form.new_customer.email"
                            />
                            <InputError
                                :message="form.errors['new_customer.email']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="new-customer-phone">Teléfono</Label>
                            <Input
                                id="new-customer-phone"
                                v-model="form.new_customer.phone"
                            />
                            <InputError
                                :message="form.errors['new_customer.phone']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="new-customer-notes">Notas</Label>
                            <Input
                                id="new-customer-notes"
                                v-model="form.new_customer.notes"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Terraza y evento</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="listing">Terraza</Label>
                            <select
                                id="listing"
                                v-model="form.listing_id"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">
                                    Selecciona una terraza…
                                </option>
                                <option
                                    v-for="listing in listings"
                                    :key="listing.id"
                                    :value="listing.id"
                                >
                                    {{ listing.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.listing_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="start-date">Fecha inicial</Label>
                            <Input
                                id="start-date"
                                v-model="form.start_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end-date">Fecha final</Label>
                            <Input
                                id="end-date"
                                v-model="form.end_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="rental-mode">Modalidad de renta</Label>
                            <select
                                id="rental-mode"
                                v-model="form.rental_mode"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="hour">Por hora</option>
                                <option value="day">Por día</option>
                                <option value="fixed">
                                    Precio fijo por evento
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="billing-quantity"
                                >Cantidad facturable</Label
                            >
                            <Input
                                id="billing-quantity"
                                v-model.number="form.billing_quantity"
                                type="number"
                                min="1"
                            />
                            <InputError
                                :message="form.errors.billing_quantity"
                            />
                        </div>

                        <div
                            v-if="form.rental_mode !== 'fixed'"
                            class="grid gap-2"
                        >
                            <Label for="start-time">Hora inicial</Label>
                            <Input
                                id="start-time"
                                v-model="form.start_time"
                                type="time"
                            />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div
                            v-if="form.rental_mode !== 'fixed'"
                            class="grid gap-2"
                        >
                            <Label for="end-time">Hora final</Label>
                            <Input
                                id="end-time"
                                v-model="form.end_time"
                                type="time"
                            />
                            <InputError :message="form.errors.end_time" />
                        </div>

                        <p
                            v-if="form.rental_mode === 'hour'"
                            class="text-xs text-muted-foreground sm:col-span-2"
                        >
                            Convención: se factura por hora completa. Si la hora
                            final es menor o igual a la inicial, se cuenta hasta
                            el día siguiente. Horas calculadas: {{ hourCount }}.
                        </p>
                        <p
                            v-else-if="form.rental_mode === 'day'"
                            class="text-xs text-muted-foreground sm:col-span-2"
                        >
                            Convención: la fecha inicial y la final cuentan como
                            días completos de renta. Días calculados:
                            {{ dateDayCount }}.
                        </p>

                        <div class="grid gap-2">
                            <Label for="event-type">Tipo de evento</Label>
                            <Input
                                id="event-type"
                                v-model="form.event_type"
                                list="event-types"
                            />
                            <datalist id="event-types">
                                <option
                                    v-for="type in selectedListing?.event_types ??
                                    []"
                                    :key="type"
                                    :value="type"
                                />
                            </datalist>
                            <InputError :message="form.errors.event_type" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="guests">Invitados</Label>
                            <Input
                                id="guests"
                                v-model="form.guests"
                                type="number"
                                min="1"
                            />
                            <InputError :message="form.errors.guests" />
                        </div>

                        <div class="sm:col-span-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="refreshBillingQuantity"
                            >
                                Recalcular cantidad y partida de renta
                            </Button>
                            <Button
                                v-if="selectedListing"
                                type="button"
                                size="sm"
                                class="ml-2"
                                @click="addRentalItem"
                            >
                                {{
                                    hasAutoRental
                                        ? 'Actualizar partida de renta'
                                        : 'Agregar renta de terraza'
                                }}
                            </Button>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">Conceptos</h2>
                            <p class="text-sm text-muted-foreground">
                                Agrega los conceptos que incluirá tu cotización.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <select
                                v-if="availableServices.length > 0"
                                class="h-9 max-w-52 rounded-md border border-input bg-transparent px-2 text-sm"
                                @change="addServiceFromSelect"
                            >
                                <option value="">
                                    Agregar servicio existente…
                                </option>
                                <option
                                    v-for="service in availableServices"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{ service.label }} —
                                    {{ money(service.price_cents) }}
                                </option>
                            </select>
                            <select
                                v-if="
                                    selectedListing &&
                                    selectedListing.packages.length
                                "
                                class="h-9 max-w-52 rounded-md border border-input bg-transparent px-2 text-sm"
                                @change="addPackageFromSelect"
                            >
                                <option value="">Agregar paquete…</option>
                                <option
                                    v-for="pack in selectedListing.packages"
                                    :key="pack.id"
                                    :value="pack.id"
                                >
                                    {{ pack.name }} —
                                    {{ money(pack.price_cents) }}
                                </option>
                            </select>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addConcept"
                            >
                                Agregar concepto
                            </Button>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" />

                    <div
                        v-if="form.items.length === 0"
                        class="mt-4 rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        Usa «Agregar renta de terraza», un servicio del catálogo
                        o «Agregar concepto» para crear una partida
                        independiente.
                    </div>

                    <div v-else class="mt-4 space-y-4">
                        <article
                            v-for="(item, index) in form.items"
                            :key="item.uid"
                            class="rounded-lg border bg-muted/20 p-4"
                        >
                            <div
                                class="mb-3 flex flex-wrap items-center justify-between gap-2"
                            >
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Partida {{ index + 1 }}
                                </p>
                                <Badge
                                    v-if="item.auto_rental"
                                    variant="secondary"
                                    class="text-[10px]"
                                >
                                    Renta automática
                                </Badge>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-12">
                                <div class="grid gap-2 sm:col-span-6">
                                    <Label :for="`item-type-${item.uid}`"
                                        >Tipo</Label
                                    >
                                    <select
                                        :id="`item-type-${item.uid}`"
                                        v-model="item.type"
                                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                        @change="onItemTypeChange(item)"
                                    >
                                        <option value="rental">Renta</option>
                                        <option value="service">
                                            Servicio
                                        </option>
                                        <option value="product">
                                            Producto
                                        </option>
                                        <option value="package">Paquete</option>
                                        <option value="custom">Otro</option>
                                    </select>
                                </div>
                                <div class="grid gap-2 sm:col-span-6">
                                    <Label :for="`item-unit-${item.uid}`"
                                        >Unidad</Label
                                    >
                                    <select
                                        :id="`item-unit-${item.uid}`"
                                        v-model="item.unit"
                                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                    >
                                        <option value="hour">Hora</option>
                                        <option value="day">Día</option>
                                        <option value="event">Evento</option>
                                        <option value="service">
                                            Servicio
                                        </option>
                                        <option value="piece">Pieza</option>
                                        <option value="person">Persona</option>
                                    </select>
                                </div>

                                <div class="grid gap-2 sm:col-span-12">
                                    <Label :for="`item-description-${item.uid}`"
                                        >Concepto</Label
                                    >
                                    <Input
                                        :id="`item-description-${item.uid}`"
                                        v-model="item.description"
                                        placeholder="Ej. Renta de terraza, servicio de sonido, refrescos"
                                    />
                                    <InputError
                                        :message="
                                            itemError(index, 'description')
                                        "
                                    />
                                </div>

                                <div class="grid gap-2 sm:col-span-12">
                                    <Label :for="`item-details-${item.uid}`"
                                        >Descripción adicional</Label
                                    >
                                    <Input
                                        :id="`item-details-${item.uid}`"
                                        v-model="item.details"
                                        placeholder="Detalles, inclusiones o condiciones (opcional)"
                                    />
                                </div>

                                <div class="grid gap-2 sm:col-span-6">
                                    <Label :for="`item-quantity-${item.uid}`"
                                        >Cantidad</Label
                                    >
                                    <Input
                                        :id="`item-quantity-${item.uid}`"
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                    />
                                    <InputError
                                        :message="itemError(index, 'quantity')"
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-6">
                                    <Label :for="`item-price-${item.uid}`"
                                        >Precio unitario ({{
                                            defaults.currency
                                        }})</Label
                                    >
                                    <Input
                                        :id="`item-price-${item.uid}`"
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                    />
                                    <InputError
                                        :message="
                                            itemError(index, 'unit_price_cents')
                                        "
                                    />
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-3"
                            >
                                <p class="text-sm font-medium">
                                    Importe:
                                    <span class="text-base font-semibold">
                                        {{
                                            money(
                                                Math.round(
                                                    (Number(item.quantity) ||
                                                        0) *
                                                        (Number(
                                                            item.unit_price,
                                                        ) || 0) *
                                                        100,
                                                ),
                                            )
                                        }}
                                    </span>
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        title="Mover arriba"
                                        @click="moveItem(index, -1)"
                                    >
                                        ↑
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        title="Mover abajo"
                                        @click="moveItem(index, 1)"
                                    >
                                        ↓
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="duplicateItem(index)"
                                    >
                                        Duplicar
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="removeItem(index)"
                                    >
                                        Eliminar
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">
                        Impuestos y descuentos
                    </h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="tax-mode">Tratamiento del IVA</Label>
                            <select
                                id="tax-mode"
                                v-model="form.tax_mode"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="none">Sin IVA desglosado</option>
                                <option value="added">
                                    Agregar IVA {{ taxRateLabel }} al subtotal
                                </option>
                                <option value="included">
                                    Precio con IVA incluido
                                </option>
                            </select>
                            <p class="text-xs text-muted-foreground">
                                El IVA es una elección comercial de esta
                                cotización; no implica una determinación fiscal.
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="discount-type">Descuento</Label>
                            <select
                                id="discount-type"
                                v-model="form.discount_type"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="none">Sin descuento</option>
                                <option value="fixed">Importe fijo</option>
                                <option value="percent">Porcentaje</option>
                            </select>
                        </div>
                        <div
                            v-if="form.discount_type !== 'none'"
                            class="grid gap-2"
                        >
                            <Label for="discount-value">
                                {{
                                    form.discount_type === 'percent'
                                        ? 'Porcentaje (%)'
                                        : `Monto (${defaults.currency})`
                                }}
                            </Label>
                            <Input
                                id="discount-value"
                                v-model.number="form.discount_input"
                                type="number"
                                min="0"
                                :max="
                                    form.discount_type === 'percent'
                                        ? 100
                                        : undefined
                                "
                                step="0.01"
                            />
                            <InputError
                                :message="fieldError('discount_value')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="deposit"
                                >Anticipo ({{ defaults.currency }})</Label
                            >
                            <Input
                                id="deposit"
                                v-model.number="form.deposit"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <InputError
                                :message="fieldError('deposit_cents')"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">
                        Condiciones comerciales
                    </h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="expires-at">Vigencia</Label>
                            <Input
                                id="expires-at"
                                v-model="form.expires_at"
                                type="date"
                            />
                            <InputError :message="form.errors.expires_at" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="terms">Términos y condiciones</Label>
                            <textarea
                                id="terms"
                                v-model="form.terms"
                                rows="4"
                                class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.terms" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="notes">Notas u observaciones</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </section>
            </fieldset>

            <aside class="space-y-4 xl:sticky xl:top-4">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-semibold">Resumen</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ money(itemSubtotalCents) }}</dd>
                        </div>
                        <div
                            v-if="discountCents > 0"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Descuento</dt>
                            <dd>-{{ money(discountCents) }}</dd>
                        </div>
                        <div
                            v-if="discountCents > 0"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Base para IVA</dt>
                            <dd>{{ money(taxBaseCents) }}</dd>
                        </div>
                        <div
                            v-if="form.tax_mode !== 'none'"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">
                                IVA {{ taxRateLabel }}
                                {{
                                    form.tax_mode === 'included'
                                        ? '(incluido)'
                                        : ''
                                }}
                            </dt>
                            <dd>{{ money(taxCents) }}</dd>
                        </div>
                        <div
                            class="flex justify-between gap-4 border-t pt-2 text-base font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>
                                {{ money(totalCents) }} {{ defaults.currency }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between gap-4 text-xs text-muted-foreground"
                        >
                            <dt>Anticipo</dt>
                            <dd>
                                {{
                                    money(
                                        Math.round(
                                            (Number(form.deposit) || 0) * 100,
                                        ),
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-5 grid gap-2">
                        <Button
                            v-if="!isLocked"
                            type="button"
                            :disabled="form.processing"
                            @click="submit('draft')"
                        >
                            Guardar borrador
                        </Button>
                        <Button
                            v-if="!isLocked"
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="submit('preview')"
                        >
                            Guardar y previsualizar
                        </Button>
                        <Button
                            v-if="!isLocked"
                            type="button"
                            variant="secondary"
                            :disabled="
                                form.processing || form.items.length === 0
                            "
                            @click="submit('issue')"
                        >
                            Emitir cotización
                        </Button>
                        <Button
                            v-if="quote?.pdf_url"
                            variant="outline"
                            as-child
                        >
                            <a
                                :href="quote.pdf_url"
                                target="_blank"
                                rel="noopener"
                            >
                                Abrir PDF emitido
                            </a>
                        </Button>
                        <Button
                            v-if="quote?.can_cancel"
                            type="button"
                            variant="destructive"
                            @click="cancelQuote"
                        >
                            Cancelar cotización
                        </Button>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        La vista previa usa exactamente el mismo cálculo y
                        renderizador que el PDF final.
                    </p>
                </section>

                <section
                    v-if="quote?.history.length"
                    class="rounded-xl border bg-card p-5"
                >
                    <h2 class="text-lg font-semibold">Historial</h2>
                    <ol class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="entry in quote.history"
                            :key="entry.id"
                            class="border-l-2 border-muted pl-3"
                        >
                            <p class="font-medium">
                                {{ entry.to_status_label ?? entry.to_status }}
                            </p>
                            <p v-if="entry.note" class="text-muted-foreground">
                                {{ entry.note }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ entry.changed_by ?? 'Sistema' }}
                                <span v-if="entry.created_at">
                                    ·
                                    {{
                                        new Date(
                                            entry.created_at,
                                        ).toLocaleString('es-MX')
                                    }}
                                </span>
                            </p>
                        </li>
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</template>
