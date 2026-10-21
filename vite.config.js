import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Inter', {
                    // 400 cuerpo · 500 medium · 600 semibold · 700 bold:
                    // los pesos que usan las utilidades del storefront.
                    weights: [400, 500, 600, 700],
                }),
                bunny('Jost', {
                    // 500 medium: el único peso que usa el wordmark `Tag Q`
                    // (utilidad `font-jost`) en navbar y footer.
                    weights: [500],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
