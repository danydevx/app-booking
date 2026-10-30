<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { StatusOption } from '../types';

const props = defineProps<{
    storeUrl: string;
    statuses: StatusOption[];
    methods: StatusOption[];
}>();

const form = useForm({
    amount: '',
    status: 'pending',
    method: 'cash',
    reference: '',
    received_at: '',
    notes: '',
});

function submit(): void {
    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('amount', 'reference', 'received_at', 'notes');
        },
    });
}
</script>

<template>
    <form
        class="grid gap-3 rounded-xl border p-4 sm:grid-cols-2"
        @submit.prevent="submit"
    >
        <div class="grid gap-2">
            <Label for="deposit-amount">Monto</Label>
            <Input
                id="deposit-amount"
                v-model="form.amount"
                inputmode="decimal"
                placeholder="1500.00"
                required
            />
            <InputError :message="form.errors.amount" />
        </div>
        <div class="grid gap-2">
            <Label for="deposit-method">Método</Label>
            <select
                id="deposit-method"
                v-model="form.method"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option
                    v-for="method in methods"
                    :key="method.value"
                    :value="method.value"
                >
                    {{ method.label }}
                </option>
            </select>
            <InputError :message="form.errors.method" />
        </div>
        <div class="grid gap-2">
            <Label for="deposit-status">Estado</Label>
            <select
                id="deposit-status"
                v-model="form.status"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <InputError :message="form.errors.status" />
        </div>
        <div class="grid gap-2">
            <Label for="deposit-reference">Referencia</Label>
            <Input
                id="deposit-reference"
                v-model="form.reference"
                maxlength="120"
                placeholder="SPEI, folio, recibo..."
            />
            <InputError :message="form.errors.reference" />
        </div>
        <div class="grid gap-2">
            <Label for="deposit-received">Recibido el</Label>
            <Input
                id="deposit-received"
                v-model="form.received_at"
                type="date"
            />
            <InputError :message="form.errors.received_at" />
        </div>
        <div class="grid gap-2">
            <Label for="deposit-notes">Notas</Label>
            <Input id="deposit-notes" v-model="form.notes" maxlength="2000" />
            <InputError :message="form.errors.notes" />
        </div>
        <div class="sm:col-span-2">
            <Button type="submit" :disabled="form.processing">
                Registrar anticipo
            </Button>
        </div>
    </form>
</template>
