<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E responsive de las pantallas de acceso (AGENTS.md §3: tests/Browser).
 *
 * Protege lo que no se puede comprobar desde PHPUnit:
 *
 * - A 390×844 (/login y /register): la tarjeta no genera scroll horizontal,
 *   los campos y el CTA son visibles y los inputs mantienen 16 px para evitar
 *   el zoom automático de iOS. El panel editorial ya no existe: la tarjeta es
 *   una sola columna centrada.
 * - A 1280×900 (/login): la tarjeta sigue centrada, sin desbordar el viewport.
 *
 * Cómo ejecutarlo (mismas condiciones que el resto de tests/Browser):
 *   1. Chromedriver disponible: `php artisan dusk:chrome-driver --detect`.
 *   2. Chrome o Chromium instalado en el sistema.
 *   3. Servir la app contra la base de pruebas:
 *        APP_ENV=testing DB_CONNECTION=pgsql DB_DATABASE=tagq_testing \
 *          php -S 127.0.0.1:8000 -t public
 *   4. `APP_URL=http://127.0.0.1:8000 php artisan dusk tests/Browser/AuthResponsiveTest.php`.
 *
 * `DatabaseMigrations` mantiene el test aislado de otros tests y seeders.
 */
class AuthResponsiveTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** Ancho del viewport de referencia (iPhone 12–15). */
    private const MOBILE_WIDTH = 390;

    /** Alto del viewport de referencia (iPhone 12–15). */
    private const MOBILE_HEIGHT = 844;

    /** Viewport de escritorio de referencia. */
    private const DESKTOP_WIDTH = 1280;

    private const DESKTOP_HEIGHT = 900;

    public function test_login_es_usable_en_movil_y_escritorio(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(self::MOBILE_WIDTH, self::MOBILE_HEIGHT)
                ->visit('/login')
                ->waitFor('[data-auth="login"]')
                ->assertVisible('[data-auth="login"] input#email')
                ->assertVisible('[data-auth="login"] input#password')
                ->assertVisible('[data-auth="login"] button[type="submit"]')
                ->assertMissing('[data-auth-editorial]')
                ->assertScript(
                    'return document.documentElement.scrollWidth'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-auth] .auth-shell")'
                    .'.getBoundingClientRect().right'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                // 16 px evita el zoom automático de iOS al enfocar un campo.
                ->assertScript(
                    'return parseFloat(getComputedStyle('
                    .'document.querySelector("[data-auth] .auth-input")'
                    .').fontSize) >= 16;',
                    true,
                )
                ->resize(self::DESKTOP_WIDTH, self::DESKTOP_HEIGHT)
                ->pause(300)
                ->assertMissing('[data-auth-editorial]')
                ->assertVisible('[data-auth="login"] button[type="submit"]')
                ->assertScript(
                    'const shell = document.querySelector("[data-auth] .auth-shell");'
                    .'const r = shell.getBoundingClientRect();'
                    .'return Math.abs((r.left + r.right) / 2 - window.innerWidth / 2) <= 2;',
                    true,
                )
                ->assertScript(
                    'return document.documentElement.scrollWidth'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                );
        });
    }

    public function test_registro_es_usable_en_movil(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(self::MOBILE_WIDTH, self::MOBILE_HEIGHT)
                ->visit('/register')
                ->waitFor('[data-auth="register"]')
                ->assertVisible('[data-auth="register"] input#name')
                ->assertVisible('[data-auth="register"] input#email')
                ->assertVisible('[data-auth="register"] input#password')
                ->assertVisible('[data-auth="register"] input#password_confirmation')
                ->assertVisible('[data-auth="register"] button[type="submit"]')
                ->assertMissing('[data-auth-editorial]')
                ->assertScript(
                    'return document.documentElement.scrollWidth'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                )
                ->assertScript(
                    'return document.querySelector("[data-auth] .auth-shell")'
                    .'.getBoundingClientRect().right'
                    .' <= document.documentElement.clientWidth + 1;',
                    true,
                );
        });
    }
}
