import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { existsSync, readdirSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, lazyPlugins, loadEnv } from 'vite-plus';

const projectRoot = fileURLToPath(new URL('.', import.meta.url));

const devServerPort = 5173;
const localHostnames = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];

function hostnameFromUrl(url: string | undefined): string | undefined {
    if (!url) {
        return undefined;
    }

    try {
        return new URL(url).hostname;
    } catch {
        return undefined;
    }
}

/**
 * When APP_URL points at a real hostname (e.g. http://booking.local), the dev
 * server has to listen on the LAN and hand out assets through that same host,
 * otherwise another machine only sees 127.0.0.1 URLs it cannot reach.
 */
function lanDevServer(appUrl: string | undefined) {
    const hostname = hostnameFromUrl(appUrl);

    if (!hostname || localHostnames.includes(hostname)) {
        return {};
    }

    const origin = `http://${hostname}:${devServerPort}`;
    const appOrigin = new URL(appUrl as string).origin;

    return {
        host: '0.0.0.0',
        port: devServerPort,
        strictPort: true,
        origin,
        // The plugin would otherwise lock CORS to `origin`, blocking modules
        // requested from the page served by Apache on http://booking.local.
        cors: { origin: [appOrigin, origin] },
        hmr: { host: hostname },
    };
}

function collectVueFiles(directory: string): string[] {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const file = join(directory, entry.name);

        if (entry.isDirectory()) {
            return collectVueFiles(file);
        }

        return entry.isFile() && entry.name.endsWith('.vue')
            ? [relative(projectRoot, file)]
            : [];
    });
}

function collectModulePages(): string[] {
    const modulesDirectory = join(projectRoot, 'Modules');

    if (!existsSync(modulesDirectory)) {
        return [];
    }

    return readdirSync(modulesDirectory, { withFileTypes: true })
        .filter((entry) => entry.isDirectory())
        .flatMap((entry) => {
            const pagesDirectory = join(
                modulesDirectory,
                entry.name,
                'resources/js/pages',
            );

            return existsSync(pagesDirectory)
                ? collectVueFiles(pagesDirectory)
                : [];
        });
}

const pageEntries = [
    ...collectVueFiles(join(projectRoot, 'resources/js/pages')),
    ...collectModulePages(),
];

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, projectRoot, ['APP_URL']);

    return {
        plugins: lazyPlugins(() => [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.ts',
                    ...pageEntries,
                ],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            inertia(),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            wayfinder({
                formVariants: true,
            }),
        ]),
        server: {
            ...lanDevServer(env.APP_URL),
            watch: {
                ignored: [
                    '**/.agents/**',
                    '**/.claude/**',
                    '**/.cursor/**',
                    '**/.junie/**',
                    '**/vendor/**',
                ],
            },
        },
        lint: {
            ignorePatterns: [
                'vendor/**',
                'node_modules/**',
                'public/**',
                'bootstrap/ssr/**',
                'tailwind.config.js',
                'resources/js/actions/**',
                'resources/js/components/ui/*',
                'resources/js/routes/**',
                'resources/js/wayfinder/**',
            ],
            options: {
                denyWarnings: true,
                typeAware: true,
            },
        },
        fmt: {
            printWidth: 80,
            tabWidth: 4,
            singleQuote: true,
            semi: true,
            singleAttributePerLine: false,
            htmlWhitespaceSensitivity: 'css',
            ignorePatterns: [
                '.github/**',
                'composer.json',
                'resources/js/components/ui/*',
                'resources/views/mail/*',
            ],
            sortTailwindcss: {
                functions: ['clsx', 'cn', 'cva'],
                stylesheet: 'resources/css/app.css',
            },
        },
    };
});
