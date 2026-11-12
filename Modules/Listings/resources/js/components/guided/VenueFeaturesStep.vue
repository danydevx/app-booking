<script setup lang="ts">
import FeaturedCharacteristicsPicker from '@/components/public/FeaturedCharacteristicsPicker.vue';
import GuidedSearchStep from './GuidedSearchStep.vue';

defineProps<{
    modelValue: string[];
    options: Array<{
        key: string;
        name: string;
        description: string | null;
        icon: string | null;
    }>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();
</script>

<template>
    <GuidedSearchStep
        title="¿Cómo imaginas el espacio para tu evento?"
        subtitle="Selecciona lo que más te gustaría encontrar. Puedes elegir varias características o continuar sin ninguna."
    >
        <FeaturedCharacteristicsPicker
            :model-value="modelValue"
            :options="options"
            :max="10"
            @update:model-value="emit('update:modelValue', $event)"
        />

        <p class="mt-4 text-xs leading-relaxed text-[color:var(--home-muted)]">
            Los resultados incluirán espacios que tengan todas las
            características seleccionadas.
        </p>
    </GuidedSearchStep>
</template>
