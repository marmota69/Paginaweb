import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/css/portfolio.css',
                'resources/js/portfolio.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Archivo', {
                    weights: [400, 600, 800],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Bind to IPv4 explicitly. Left to itself Vite listens only on [::1],
        // and the dev server URL it writes to public/hot then points the
        // browser at a host it cannot reach from http://127.0.0.1 — the
        // scripts silently fail to load and every JS-driven control dies.
        host: '127.0.0.1',
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
