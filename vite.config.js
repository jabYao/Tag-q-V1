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
    /*
     * Source maps del CSS y del JS compilados.
     *
     * Sin esto, el navegador solo conoce `public/build/assets/app-<hash>.css`:
     * un único archivo minificado y sin referencia al origen, así que DevTools
     * no puede llevar a la regla que se quiere tocar. Con los mapas, cada regla
     * de `resources/css/app.css` (y de `admin.css`) apunta a su archivo y línea
     * reales. Las utilidades que genera Tailwind a partir de los Blade son
     * clases compuestas: para esas, el mapa apunta al CSS de entrada, no a la
     * vista, porque no existen como texto en ningún archivo fuente.
     *
     * `public/build` está en `.gitignore`, así que los `.map` no se commitean.
     */
    build: {
        sourcemap: true,
    },
    /*
     * Source maps de CSS en el servidor de desarrollo (`npm run dev`).
     *
     * En desarrollo Vite sirve el CSS sin minificar y con su propio nombre de
     * archivo, así que el inspector del navegador ya lo muestra legible. Esta
     * opción agrega además el mapa: el inspector enlaza cada regla con la línea
     * exacta de `resources/css/app.css` o `admin.css`. Es el modo en el que
     * conviene tocar tamaños y colores a mano.
     */
    css: {
        devSourcemap: true,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
