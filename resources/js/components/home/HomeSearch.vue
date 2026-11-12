<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type MunicipalityIndex = Record<
    string,
    { label: string; municipalities: string[] }
>;

const props = defineProps<{
    action: string;
    locations: MunicipalityIndex;
    services: Array<{ value: string; label: string }>;
}>();

const location = ref('');
const guests = ref<string | number>('');
const service = ref('');

const locationGroups = computed(() =>
    Object.entries(props.locations).map(([state, data]) => ({
        state,
        label: data.label,
        cities: data.municipalities.map((city) => ({
            value: `${state}||${city}`,
            label: city,
        })),
    })),
);

function submit(): void {
    const params: Record<string, string | number> = {};

    if (location.value !== '') {
        const [state, city] = location.value.split('||');

        if (state) {
            params.state = state;
        }

        if (city) {
            params.city = city;
        }
    }

    if (guests.value !== '' && guests.value !== null) {
        params.capacity = guests.value;
    }

    if (service.value !== '') {
        params.service = service.value;
    }

    router.get(props.action, params);
}
</script>

<template>
    <form class="home-search p-3 sm:p-4" @submit.prevent="submit">
        <div
            class="grid gap-1 lg:grid-cols-[1.5fr_1fr_1.2fr_auto] lg:items-stretch"
        >
            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:border-r lg:border-[color:var(--home-border)] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-location">
                    Ubicación
                </label>
                <select
                    id="home-location"
                    v-model="location"
                    class="home-select"
                >
                    <option value="">Todo México</option>
                    <optgroup
                        v-for="group in locationGroups"
                        :key="group.state"
                        :label="group.label"
                    >
                        <option
                            v-for="city in group.cities"
                            :key="city.value"
                            :value="city.value"
                        >
                            {{ city.label }}
                        </option>
                    </optgroup>
                </select>
            </div>

            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:border-r lg:border-[color:var(--home-border)] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-guests">
                    Invitados
                </label>
                <input
                    id="home-guests"
                    v-model="guests"
                    class="home-input"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    placeholder="¿Cuántos asisten?"
                />
            </div>

            <div
                class="home-search-field rounded-[0.875rem] px-4 py-2 transition-colors hover:bg-[#fafafa] lg:hover:bg-transparent"
            >
                <label class="home-field-label" for="home-service">
                    Característica
                </label>
                <select id="home-service" v-model="service" class="home-select">
                    <option value="">Cualquier característica</option>
                    <option
                        v-for="option in services"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div class="p-1 lg:flex lg:items-stretch lg:pl-2">
                <button
                    type="submit"
                    class="home-btn home-btn--primary h-12 w-full px-6 lg:w-auto lg:px-7"
                >
                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <circle
                            cx="9"
                            cy="9"
                            r="6.25"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="M13.5 13.5L17.5 17.5"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                    Buscar
                </button>
            </div>
        </div>
    </form>
</template>
