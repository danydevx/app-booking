<script setup lang="ts">
import { computed } from 'vue';
import { Info } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { TeamInvitationContext } from '@/types';

type Props = {
    invitation: TeamInvitationContext;
    action: 'login' | 'register';
};

const props = defineProps<Props>();

const message = computed(() =>
    props.action === 'login'
        ? `Inicia sesión para unirte al equipo "${props.invitation.teamName}".`
        : `Regístrate para unirte al equipo "${props.invitation.teamName}".`,
);
</script>

<template>
    <div data-test="team-invitation-alert">
        <Alert
            class="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400"
        >
            <Info class="size-4" />
            <AlertDescription class="text-blue-900 dark:text-blue-100">
                {{ message }}
            </AlertDescription>
        </Alert>
    </div>
</template>
