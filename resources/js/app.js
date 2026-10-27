import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { gsap } from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

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
 * El top bar de envíos y la navbar son hermanos al principio del `<body>`.
 * La navbar es sticky: al scrollear se pega al borde superior, así que su
 * `getBoundingClientRect().bottom` deja de representar la cabecera completa.
 * La altura estable se obtiene sumando el alto del top bar y el de la navbar;
 * esa suma no depende del scroll. Se publica en px sobre `<html>` para que el
 * hero del home se dibuje como `100dvh - cabecera` y quede pegado a la navbar.
 * Se recalcula al cargar y al redimensionar; las páginas sin navbar (panel de
 * administración) no hacen nada. El script es `type="module"`, así que ya corre
 * con el DOM listo.
 *
 * Publica además el alto de la navbar sola (`--storefront-nav-h`): la barra
 * transparente que separa la cabecera del hero del home mide exactamente eso.
 */
function publishStorefrontHeaderHeight() {
    const nav = document.querySelector('[data-storefront-nav]');

    if (!nav) {
        return;
    }

    const topbar = document.querySelector('[data-shipping-topbar]');
    const height = (topbar ? topbar.offsetHeight : 0) + nav.offsetHeight;

    document.documentElement.style.setProperty('--storefront-header-h', `${height}px`);
    document.documentElement.style.setProperty('--storefront-nav-h', `${nav.offsetHeight}px`);
}

publishStorefrontHeaderHeight();
window.addEventListener('resize', publishStorefrontHeaderHeight);

// Auto-corrección: si la cabecera cambia de alto después del primer pintado
// (cambio de breakpoint, reflow), el ResizeObserver vuelve a publicar la
// medida y el hero vuelve a encajar en el viewport sin esperar un resize.
if (typeof ResizeObserver === 'function') {
    const nav = document.querySelector('[data-storefront-nav]');

    if (nav) {
        const topbar = document.querySelector('[data-shipping-topbar]');
        const observer = new ResizeObserver(publishStorefrontHeaderHeight);

        observer.observe(nav);

        if (topbar) {
            observer.observe(topbar);
        }
    }
}

/**
 * Animación editorial del hero (`data-home-hero-slider`), aprobada por el
 * dueño: la imagen activa entra con un zoom suave (1.04 → 1) y un desvanecido
 * leve, y el copy activo entra por capas —titular, cuerpo y acciones— con un
 * escalonado corto (0 ms, 100 ms y 180 ms). Sin cortina de color: el copy
 * inactivo se retira antes de entrar el nuevo, así nunca se solapan. Se ejecuta
 * en la carga inicial y en cada cambio de slide, con GSAP (dependencia ya
 * presente), sin añadir ninguna otra.
 */
const HERO_MEDIA_ZOOM_START = 1.04;
const HERO_MEDIA_ZOOM_END = 1;
const HERO_COPY_STAGGER = [0, 0.1, 0.18];
const HERO_MEDIA_ENTRY_FROM = { scale: HERO_MEDIA_ZOOM_START, autoAlpha: 0.72 };
const HERO_MEDIA_ENTRY_TO = { scale: HERO_MEDIA_ZOOM_END, autoAlpha: 1, duration: 1.1, ease: 'power2.out' };
const HERO_COPY_ENTRY_FROM = { y: 20, autoAlpha: 0 };
const HERO_COPY_ENTRY_TO = { y: 0, autoAlpha: 1, duration: 0.6, ease: 'power3.out' };

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

    /*
     * T22/T23 — texto por diapositiva y entrada editorial, sin cortina.
     *
     * Cada diapositiva trae su propio bloque de texto (`data-home-hero-copy`);
     * el servidor pinta solo el primero y el resto llega con `hidden`. Al
     * cambiar de slide se retira el copy inactivo —sin solaparse con el nuevo—
     * y se muestra el de la diapositiva activa, usando el índice real de
     * Swiper. La cortina de color y su animación por Web Animations se
     * eliminaron por pedido del dueño: el carrusel, sus controles, el autoplay
     * y el respeto a `prefers-reduced-motion` siguen intactos.
     */
    const copies = Array.from(section.querySelectorAll('[data-home-hero-copy]'));

    /** Capas que entran escalonadas dentro de un bloque de copy. */
    const copyLayers = (copy) => [
        copy.querySelector('[data-home-hero-title]'),
        copy.querySelector('[data-home-hero-body]'),
        copy.querySelector('[data-home-hero-actions]'),
    ].filter((layer) => layer !== null);

    /** Imagen de la diapositiva activa: es la que recibe el zoom. */
    const activeMedia = () => {
        const slide = swiper.slides[swiper.activeIndex];

        return slide ? slide.querySelector('[data-home-hero-photo]') : null;
    };

    /** Devuelve las capas a su estado base, sin restos inline de GSAP. */
    const settle = (targets) => {
        if (targets.length === 0) {
            return;
        }

        gsap.killTweensOf(targets);
        gsap.set(targets, { clearProps: 'transform,opacity,visibility' });
    };

    const copyFor = (index) => copies.find((copy) => Number(copy.dataset.homeHeroCopy) === index) ?? null;

    const showCopy = (index) => {
        copies.forEach((copy) => {
            const active = Number(copy.dataset.homeHeroCopy) === index;

            if (!active) {
                settle(copyLayers(copy));
            }

            copy.hidden = !active;
        });
    };

    /**
     * Entrada editorial de la diapositiva activa: zoom de la imagen y copy
     * escalonado. Con movimiento reducido no anima nada y deja el copy a la
     * vista de inmediato.
     */
    const enterActive = (index) => {
        const media = activeMedia();
        const copy = copyFor(index);
        const layers = copy ? copyLayers(copy) : [];

        settle(media ? [media] : []);
        settle(layers);

        if (reducedMotion.matches) {
            return;
        }

        const timeline = gsap.timeline();

        if (media) {
            timeline.fromTo(
                media,
                HERO_MEDIA_ENTRY_FROM,
                { ...HERO_MEDIA_ENTRY_TO, clearProps: 'transform,opacity,visibility' },
                0,
            );
        }

        layers.forEach((layer, order) => {
            timeline.fromTo(
                layer,
                HERO_COPY_ENTRY_FROM,
                { ...HERO_COPY_ENTRY_TO, clearProps: 'transform,opacity,visibility' },
                HERO_COPY_STAGGER[order] ?? order * 0.1,
            );
        });
    };

    swiper.on('slideChange', () => {
        const index = swiper.realIndex;

        showCopy(index);
        enterActive(index);
    });

    // La carga inicial también entra: el hero arranca con la primera diapositiva.
    showCopy(swiper.realIndex);
    enterActive(swiper.realIndex);

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

    reducedMotion.addEventListener('change', (event) => {
        if (!event.matches) {
            resume();
            return;
        }

        // El visitante pidió movimiento reducido: se pausa el autoplay y la
        // entrada editorial se resuelve al instante, sin zoom ni escalonado.
        pause();
        showCopy(swiper.realIndex);
        enterActive(swiper.realIndex);
    });
}

document.querySelectorAll('[data-home-hero-slider]').forEach((section) => initHomeHeroSlider(section));

/**
 * Animaciones del home posterior al hero (GSAP + ScrollTrigger).
 *
 * El hero queda fuera a propósito: conserva su carrusel Swiper propio y nada
 * de esta coreografía lo toca (el contenedor raíz es `data-home-body`, hermano
 * posterior del hero). El resto de la portada
 * —títulos, tarjetas de categoría, tarjetas de producto, cierre y galería
 * bento— entra con un `stagger` al hacer scroll, y las imágenes hacen un zoom
 * leve dentro de su marco.
 *
 * El contenido SIEMPRE viene visible desde el servidor: este script solo aplica
 * el estado inicial con GSAP, así que sin JavaScript la portada se lee y se
 * navega igual. Con `prefers-reduced-motion: reduce` la función sale antes de
 * ocultar nada y no se ejecuta ninguna animación.
 *
 * Ganchos declarativos que conecta:
 *   - `data-home-body`          contenedor raíz (excluye el hero).
 *   - `data-home-stagger`       contenedor cuyos hijos `data-home-reveal`
 *                              entran escalonados.
 *   - `data-home-reveal`        elemento que se revela al entrar en viewport.
 *   - `data-home-reveal-media`  marco cuya imagen interior hace zoom de entrada.
 *
 * GSAP escribe `transform` inline mientras anima; como las tarjetas e imágenes
 * también reaccionan al hover por CSS, aquí se apaga su `transition` durante el
 * tween y se limpia al terminar (`clearProps`), para que la entrada y el hover
 * convivan sin pelearse por la misma propiedad.
 */

/**
 * ¿El visitante pidió movimiento reducido? Fuente única de la decisión.
 */
function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Estado inicial compartido: aparece deslizándose un poco desde abajo. */
const HOME_REVEAL_FROM = { autoAlpha: 0, y: 28 };
const HOME_REVEAL_TO = { autoAlpha: 1, y: 0, duration: 0.7, ease: 'power3.out' };

/** Zoom de entrada del marco de imagen: de un poco más grande a su tamaño real. */
const HOME_MEDIA_FROM = { scale: 1.14 };
const HOME_MEDIA_TO = { scale: 1, duration: 1.2, ease: 'power2.out' };

/**
 * Marco de imagen de un elemento: puede ser el propio elemento
 * (`data-home-reveal-media` en la misma etiqueta) o uno interior.
 */
function homeMediaFrame(element) {
    return element.matches('[data-home-reveal-media]')
        ? element
        : element.querySelector('[data-home-reveal-media]');
}

/**
 * Revela un elemento dentro de una línea de tiempo. Neutraliza antes su
 * `transition` de CSS y la devuelve al terminar (`clearProps`).
 */
function addHomeReveal(timeline, element, position) {
    gsap.set(element, { transition: 'none' });

    timeline.fromTo(
        element,
        HOME_REVEAL_FROM,
        { ...HOME_REVEAL_TO, clearProps: 'transform,transition' },
        position,
    );
}

/**
 * Hace el zoom de entrada de la primera imagen del marco indicado, si existe.
 */
function addHomeMediaZoom(timeline, frame, position) {
    const image = frame ? frame.querySelector('img') : null;

    if (!image) {
        return;
    }

    gsap.set(image, { transition: 'none' });

    timeline.fromTo(
        image,
        HOME_MEDIA_FROM,
        { ...HOME_MEDIA_TO, clearProps: 'transform,transition' },
        position,
    );
}

/**
 * Entrada escalonada de un contenedor `data-home-stagger`.
 */
function initHomeStagger(container) {
    const items = Array.from(container.children).filter((child) => child.matches('[data-home-reveal]'));

    if (items.length === 0) {
        return;
    }

    const timeline = gsap.timeline({
        scrollTrigger: { trigger: container, start: 'top 82%', once: true },
    });

    items.forEach((item, index) => {
        const position = index * 0.09;

        addHomeReveal(timeline, item, position);
        addHomeMediaZoom(timeline, homeMediaFrame(item), position);
    });
}

/**
 * Entrada suelta para los títulos y bloques que no viven en un stagger.
 */
function initHomeReveal(element) {
    const timeline = gsap.timeline({
        scrollTrigger: { trigger: element, start: 'top 88%', once: true },
    });

    addHomeReveal(timeline, element, 0);
    addHomeMediaZoom(timeline, homeMediaFrame(element), 0);
}

/**
 * Arranca la coreografía del home posterior al hero. Sin contenedor propio, o
 * con movimiento reducido, no hace nada: el contenido queda tal como lo pintó
 * el servidor.
 */
function initHomeAnimations() {
    const root = document.querySelector('[data-home-body]');

    if (!root || prefersReducedMotion()) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    root.querySelectorAll('[data-home-stagger]').forEach((container) => initHomeStagger(container));

    root.querySelectorAll('[data-home-reveal]').forEach((element) => {
        const parent = element.parentElement;

        // Los hijos directos de un stagger ya entran en su línea de tiempo.
        if (parent && parent.matches('[data-home-stagger]')) {
            return;
        }

        initHomeReveal(element);
    });

    // Las imágenes del storefront llegan en diferido y cambian la altura de la
    // página: al terminar la carga se recalculan los puntos de disparo.
    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

initHomeAnimations();
