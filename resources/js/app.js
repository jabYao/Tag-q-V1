import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';

// Alpine lo aporta Livewire 4: `@livewireScripts` embebe su propia instancia,
// la publica en `window.Alpine` y la arranca en `DOMContentLoaded`. Arrancar
// aquí una segunda instancia reasigna `window.Alpine` antes de que Livewire
// inicialice, y Livewire ignora las raíces anidadas dentro de un `x-data` ya
// inicializado: el `wire:id` del panel de búsqueda (primer `wire:id` dentro de
// la navbar) nunca se registraba y escribir no emitía ningún
// `POST /livewire/update`.

/**
 * Alto real de la cabecera del storefront (`--storefront-header-h`).
 *
 * El top bar de envíos y la navbar son hermanos al principio del `<body>`, así
 * que el borde inferior de la navbar ya suma ambos. Esa medida se publica en px
 * sobre `<html>` para que el hero del home se dibuje como
 * `100dvh - cabecera` y quede pegado a la navbar. Se recalcula al cargar y al
 * redimensionar; las páginas sin navbar (panel de administración) no hacen
 * nada. El script es `type="module"`, así que ya corre con el DOM listo.
 */
function publishStorefrontHeaderHeight() {
    const nav = document.querySelector('[data-storefront-nav]');

    if (!nav) {
        return;
    }

    document.documentElement.style.setProperty(
        '--storefront-header-h',
        `${nav.getBoundingClientRect().bottom}px`,
    );
}

publishStorefrontHeaderHeight();
window.addEventListener('resize', publishStorefrontHeaderHeight);

/**
 * Carrusel del hero del home (`data-home-hero-slider`).
 *
 * Arranca en bucle con las tres imágenes del hero, avanza solo cada 5000 ms (el
 * valor declarado en `data-home-hero-autoplay`), y se pausa mientras el puntero
 * o el foco están dentro del carrusel. Con `prefers-reduced-motion: reduce`
 * nunca arranca solo: el carrusel queda navegable a mano. La preferencia se
 * reevalúa si cambia con la página abierta. El arrastre táctil es nativo de
 * Swiper y la paginación por puntos solo se puede pulsar si hay JS.
 */
function initHomeHeroSlider(section) {
    const container = section.querySelector('[data-home-hero-swiper]');

    if (!container) {
        return;
    }

    const delay = Number(section.dataset.homeHeroAutoplay) || 5000;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const swiper = new Swiper(container, {
        modules: [Autoplay, Navigation, Pagination],
        loop: true,
        autoplay: reducedMotion.matches ? false : { delay, disableOnInteraction: false },
        pagination: {
            el: section.querySelector('[data-home-hero-pagination]'),
            clickable: true,
        },
        navigation: {
            prevEl: section.querySelector('[data-home-hero-prev]'),
            nextEl: section.querySelector('[data-home-hero-next]'),
        },
    });

    const pause = () => swiper.autoplay && swiper.autoplay.stop();

    const resume = () => {
        if (!swiper.autoplay || reducedMotion.matches) {
            return;
        }

        if (section.matches(':hover') || section.contains(document.activeElement)) {
            return;
        }

        swiper.autoplay.start();
    };

    section.addEventListener('mouseenter', pause);
    section.addEventListener('mouseleave', resume);
    section.addEventListener('focusin', pause);
    section.addEventListener('focusout', resume);

    reducedMotion.addEventListener('change', (event) => (event.matches ? pause() : resume()));
}

document.querySelectorAll('[data-home-hero-slider]').forEach((section) => initHomeHeroSlider(section));
