import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PlatformLayout from '@/layouts/PlatformLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

type PageModule = { default: DefineComponent };
type PageLoader = () => Promise<PageModule>;

const pages: Record<string, PageLoader> = {};

for (const [path, loader] of Object.entries(
    import.meta.glob<PageModule>('./pages/**/*.vue'),
)) {
    pages[path.replace('./pages/', '').replace(/\.vue$/, '')] = loader;
}

for (const [path, loader] of Object.entries(
    import.meta.glob<PageModule>('../../Modules/*/resources/js/pages/**/*.vue'),
)) {
    const match = path.match(
        /\/Modules\/([^/]+)\/resources\/js\/pages\/(.+)\.vue$/,
    );

    if (match) {
        pages[`${match[1]}/${match[2]}`] = loader;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const loader = pages[name];

        if (!loader) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return (await loader()).default;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('Listings/Public/'):
            case name.startsWith('Bookings/Public/'):
                return null;
            case name.startsWith('Platform/Public/'):
                return null;
            case name.startsWith('Platform/'):
                return PlatformLayout;
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
