<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Claro', icon: Sun },
    { value: 'dark', label: 'Oscuro', icon: Moon },
    { value: 'system', label: 'Sistema', icon: Monitor },
] as const satisfies ReadonlyArray<{
    value: Appearance;
    label: string;
    icon: typeof Sun;
}>;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="size-9"
                aria-label="Cambiar tema"
            >
                <Sun class="size-5 opacity-80 dark:hidden" />
                <Moon class="hidden size-5 opacity-80 dark:block" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuLabel>Tema</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                @select="updateAppearance(option.value)"
            >
                <component :is="option.icon" class="size-4" />
                <span>{{ option.label }}</span>
                <Check
                    v-if="appearance === option.value"
                    class="ml-auto size-4"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
