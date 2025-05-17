import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            // Force Vite to use HTTPS for all asset URLs
            transformOnServe: (code, devServer) => {
                return code.replace(/http:\/\/localhost:\d+/g, () => {
                    const { protocol, hostname } = new URL(devServer.resolvedUrls?.local[0] || '');
                    return `${protocol}//${hostname}`;
                });
            },
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    server: {
        cors: {
            origin: '*',
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization']
        },
        hmr: {
            host: 'samplepal_leads.test'
        },
    },
});
