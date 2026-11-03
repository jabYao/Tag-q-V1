<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E del home en un viewport de móvil (AGENTS.md §3).
 *
 * Protege los arreglos visuales que no se pueden comprobar desde PHPUnit:
 *
 * - La navbar no desborda a 390 px: la marca, el menú y las utilidades caben y
 *   la bolsa termina dentro del viewport. El teléfono y la cuenta se ocultan;
 *   la cuenta vive dentro del menú hamburguesa, que tiene scroll propio.
 * - La franja DESTACADO es compacta y el hero mide 62dvh, así que la siguiente
 *   sección asoma en el primer viewport y la paginación queda visible.
 * - La grilla de recientes usa dos columnas y su "Ver todos" va después.
 * - La portada móvil de categorías muestra tres fichas (una grande y dos
 *   pequeñas) y reemplaza a la galería de escritorio, que queda oculta.
 * - El carrusel de favoritos muestra tres fichas mini por pantalla, se mueve
 *   solo en bucle continuo y no tiene flechas ni indicadores; sigue siendo
 *   arrastrable y el "Ver todos" va después del carrusel.
 *
 * Cómo ejecutarlo (mismas condiciones que el resto de tests/Browser):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`.
 *   2. Servir la app contra la base de pruebas (con `php -S` porque
 *      `php artisan serve` no propaga todas las variables de entorno):
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing \
 *          php -S 127.0.0.1:8000 -t public
 *   3. Arrancar `php artisan dusk` con `APP_URL` apuntando a ese servidor.
 *   4. PostgreSQL con la base `tagq_testing` creada.
 *
 * Los datos se preparan en setUp y los limpia DatabaseMigrations, así que el
 * test no depende de seeders ni de productos de producción.
 */
class HomeMobileTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Ancho del viewport de referencia (iPhone 12–15). */
    private const VIEWPORT_WIDTH = 390;

    /** Alto del viewport de referencia. */
    private const VIEWPORT_HEIGHT = 844;

    /**
     * Productos del catálogo: seis llenan recientes y ocho permiten que el
     * carrusel de favoritos active el bucle con tres fichas visibles.
     */
    private const PRODUCTS_ON_HOME = 8;

    protected function setUp(): void
    {
        parent::setUp();

        // Las tres categorías reales de la portada móvil, con sus slugs de
        // filtro. Los productos cuelgan de Caballero.
        $caballero = Category::create([
            'name' => 'Caballero Home Móvil E2E',
            'slug' => 'caballero',
        ]);

        Category::create([
            'name' => 'Dama Home Móvil E2E',
            'slug' => 'dama',
        ]);

        Category::create([
            'name' => 'Niños Home Móvil E2E',
            'slug' => 'ninos',
        ]);

        foreach (range(1, self::PRODUCTS_ON_HOME) as $index) {
            $product = Product::create([
                'category_id' => $caballero->id,
                'name' => 'Dusk Home Móvil '.$index,
                'slug' => 'dusk-home-movil-'.$index,
                'precio' => 100000 * $index,
                'precio_oferta' => $index % 2 === 0 ? 80000 * $index : null,
                'stock' => 5,
                'is_active' => true,
                'marca' => 'Tag Heuer',
                'movimiento' => 'automatico',
                'reference' => 'DUSK-'.$index,
            ]);

            // Un producto antiguo produce el estado Disponible; el resto son
            // Nuevos. Así el test comprueba qué chips conserva la ficha mini.
            if ($index === self::PRODUCTS_ON_HOME) {
                $product->forceFill(['created_at' => now()->subDays(60)])->save();
            }
        }
    }

    public function test_el_home_movil_es_compacto_y_responsive(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(self::VIEWPORT_WIDTH, self::VIEWPORT_HEIGHT)
                ->visit('/')
                // La grilla se pide por presencia, no por visibilidad: GSAP
                // oculta las tarjetas bajo el pliegue hasta que entran en
                // pantalla, y este test mide el layout sin scrollear.
                ->waitUntil(
                    'document.querySelector("[data-home-products-grid] [data-home-card]") !== null',
                    10,
                )
                ->waitUntil(
                    'document.querySelector("[data-home-favorites-swiper]")'
                    .'.classList.contains("swiper-initialized")',
                    10,
                )
                // La medida de la franja la publica `app.js`; sin ella el hero
                // usaría el respaldo de la navbar y la paginación quedaría
                // unos píxeles fuera.
                ->waitUntil(
                    'parseFloat(getComputedStyle(document.documentElement)'
                    .'.getPropertyValue("--storefront-hero-spacer-h")) > 0',
                    10,
                )
                ->waitUntil('document.fonts.status === "loaded"', 10)
                ->pause(300)
                ->assertScript(
                    'return document.documentElement.scrollWidth'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-nav-utility=bag]")'
                    .'.getBoundingClientRect().right'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                // Teléfono y cuenta salen del navbar en móvil.
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-shipping-phone]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-nav-account]")'
                    .').display === "none";',
                    true,
                )
                // La franja DESTACADO no consume el viewport.
                ->assertScript(
                    'return document.querySelector("[data-home-hero-spacer]")'
                    .'.getBoundingClientRect().height <= 72;',
                    true,
                )
                // El hero mide 62dvh y cierra dentro del primer viewport.
                ->assertScript(
                    'return document.querySelector("[data-home-hero]")'
                    .'.getBoundingClientRect().height'
                    .' <= document.documentElement.clientHeight * 0.70 + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-home-hero]")'
                    .'.getBoundingClientRect().bottom'
                    .' <= document.documentElement.clientHeight + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-home-hero-pagination]")'
                    .'.getBoundingClientRect().bottom'
                    .' <= document.documentElement.clientHeight + 1;',
                    true,
                )
                // Recientes: dos columnas y Ver todos móvil después de la grilla.
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-products-grid] > div")'
                    .').gridTemplateColumns.split(" ").length === 2;',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-ver-todos=recent-desktop]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'const link = document.querySelector("[data-home-ver-todos=recent-mobile]");'
                    .'const grid = document.querySelector("[data-home-products-grid]");'
                    .'return getComputedStyle(link).display !== "none"'
                    .' && link.getBoundingClientRect().top >= grid.getBoundingClientRect().bottom;',
                    true,
                )
                // Categorías móviles: tres fichas, una grande arriba y dos
                // pequeñas abajo; la galería de escritorio queda oculta.
                ->assertScript(
                    'return document.querySelectorAll("[data-home-category-card]").length === 3;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-home-categories] h2")'
                    .'.textContent.trim() === "Categorías";',
                    true,
                )
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-gallery]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'const cards = document.querySelectorAll("[data-home-category-card]");'
                    .'const large = cards[0].getBoundingClientRect();'
                    .'const small = cards[1].getBoundingClientRect();'
                    .'const third = cards[2].getBoundingClientRect();'
                    .'return large.width > small.width * 1.7'
                    .' && large.top < small.top'
                    .' && Math.abs(small.top - third.top) < 2;',
                    true,
                )
                // Favoritos: tres fichas mini por pantalla.
                ->assertScript(
                    'const swiper = document.querySelector("[data-home-favorites-swiper]");'
                    .'const slide = swiper.querySelector(".swiper-slide");'
                    .'const ratio = swiper.clientWidth / slide.getBoundingClientRect().width;'
                    .'return ratio > 2.8 && ratio < 3.2;',
                    true,
                )
                // Sin flechas ni indicadores: la marquesina se mueve sola.
                ->assertScript(
                    'const section = document.querySelector("[data-home-favorites]");'
                    .'return section.querySelectorAll('
                    .'"[data-home-favorites-prev], [data-home-favorites-next],'
                    .' [data-home-favorites-pagination], .swiper-button-prev,'
                    .' .swiper-button-next, .swiper-pagination").length === 0;',
                    true,
                )
                ->assertScript(
                    'const swiper = document.querySelector("[data-home-favorites-swiper]").swiper;'
                    .'return swiper.params.loop === true'
                    .' && swiper.params.autoplay.delay === 0'
                    .' && getComputedStyle(swiper.wrapperEl).transitionTimingFunction === "linear"'
                    .' && swiper.autoplay.running === true;',
                    true,
                )
                // La ficha mini esconde la información secundaria y deja el
                // icono de la bolsa; conserva nombre y precio.
                ->assertScript(
                    'const card = document.querySelector("[data-home-favorites-swiper] [data-favorite-card]");'
                    .'const hidden = (selector) => getComputedStyle(card.querySelector(selector)).display === "none";'
                    .'return hidden("[data-home-card-kicker]")'
                    .' && hidden("[data-home-card-meta]")'
                    .' && hidden("[data-home-card-discount]")'
                    .' && hidden("[data-home-card-price-before]")'
                    .' && hidden("[data-compact-cart-label]")'
                    .' && getComputedStyle(card.querySelector("[data-compact-cart-icon]")).display !== "none"'
                    .' && getComputedStyle(card.querySelector("[data-product-card-name]")).display !== "none"'
                    .' && getComputedStyle(card.querySelector("[data-home-card-price]")).display !== "none";',
                    true,
                )
                ->assertScript(
                    'const cards = Array.from(document.querySelectorAll('
                    .'"[data-home-favorites-swiper] [data-stock-state=Disponible]"));'
                    .'return cards.every((chip) => getComputedStyle(chip).display === "none");',
                    true,
                )
                // Ver todos de favoritos: visible después del carrusel en móvil.
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-home-ver-todos=favorites-desktop]")'
                    .').display === "none";',
                    true,
                )
                ->assertScript(
                    'const link = document.querySelector("[data-home-ver-todos=favorites-mobile]");'
                    .'const swiper = document.querySelector("[data-home-favorites-swiper]");'
                    .'return getComputedStyle(link).display !== "none"'
                    .' && link.getBoundingClientRect().top >= swiper.getBoundingClientRect().bottom;',
                    true,
                )
                // En móvil el cierre de favoritos no deja un hueco grande antes
                // del footer: margen de 16px más el padding inferior del home.
                ->assertScript(
                    'const section = document.querySelector("[data-home-favorites]");'
                    .'const footer = document.querySelector("[data-storefront-footer]");'
                    .'return footer.getBoundingClientRect().top'
                    .' - section.getBoundingClientRect().bottom <= 56;',
                    true,
                );
        });
    }

    public function test_el_menu_movil_alberga_la_cuenta_y_hace_scroll_propio(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(self::VIEWPORT_WIDTH, self::VIEWPORT_HEIGHT)
                ->visit('/')
                ->click('[data-nav-mobile-toggle]')
                ->waitFor('[data-nav-mobile-account="login"]', 10)
                ->assertVisible('[data-nav-mobile-account="login"]')
                ->assertVisible('[data-nav-mobile-account="register"]')
                ->assertScript(
                    'const panel = document.querySelector("[data-nav-mobile-panel]");'
                    .'const style = getComputedStyle(panel);'
                    .'return style.overflowY === "auto"'
                    .' && panel.getBoundingClientRect().height'
                    .' <= document.documentElement.clientHeight * 0.70 + 1;',
                    true,
                )
                // Tienda recoge sus categorías en un submenú que solo aparece
                // al tocar el botón, y se vuelve a cerrar con otro toque.
                ->assertScript(
                    'return getComputedStyle('
                    .'document.querySelector("[data-nav-mobile-tienda-submenu]")'
                    .').display === "none";',
                    true,
                )
                ->click('[data-nav-mobile-item="tienda"]')
                ->waitUntil(
                    'getComputedStyle(document.querySelector('
                    .'"[data-nav-mobile-tienda-submenu]")).display !== "none";',
                    10,
                )
                ->assertVisible('[data-nav-mobile-tienda-submenu]')
                ->assertVisible('[data-nav-mobile-child="ninos"]')
                ->click('[data-nav-mobile-item="tienda"]')
                ->waitUntil(
                    'getComputedStyle(document.querySelector('
                    .'"[data-nav-mobile-tienda-submenu]")).display === "none";',
                    10,
                );
        });
    }
}
