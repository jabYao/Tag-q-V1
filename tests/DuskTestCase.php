<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Base de los tests E2E con Laravel Dusk (tests/Browser, ver AGENTS.md §3).
 *
 * Parte de la plantilla de la versión instalada (laravel/dusk v8.7.0,
 * vendor/laravel/dusk/stubs/DuskTestCase.stub) con dos ajustes:
 *
 * 1. No usa el trait `Tests\CreatesApplication`: ese trait no existe en este
 *    repositorio y Laravel 11+ ya trae `createApplication()` en
 *    Illuminate\Foundation\Testing\TestCase.
 * 2. Fija el entorno de pruebas (APP_ENV y DB_*) antes de arrancar la
 *    aplicación, para que Dusk corra contra PostgreSQL `tagq_testing` igual
 *    que el resto de la suite (phpunit.xml), sin tocar `.env`,
 *    `.env.example`, `phpunit.xml` ni crear `phpunit.dusk.xml`.
 */
abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Entorno mínimo equivalente al bloque <php> de phpunit.xml.
     *
     * Por qué existe: `php artisan dusk` genera su propio phpunit.dusk.xml
     * temporal (copiado de vendor/laravel/dusk/stubs/phpunit.xml) y ese archivo
     * no declara variables de entorno. Sin estas variables el proceso de Dusk
     * cargaría la base de datos de `.env` y el trait DatabaseMigrations
     * ejecutaría `migrate:fresh` sobre ella. Fijarlas aquí garantiza que el E2E
     * apunte siempre a la base de pruebas.
     *
     * Son los mismos valores por defecto que ya usa phpunit.xml y no contienen
     * credenciales: host, usuario y contraseña siguen proviniendo de la
     * configuración de la aplicación.
     *
     * @var array<string, string>
     */
    private const TESTING_ENVIRONMENT = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'pgsql',
        'DB_DATABASE' => 'tagq_testing',
        'DB_URL' => '',
    ];

    /**
     * Fija el entorno de pruebas y arranca la aplicación.
     */
    protected function setUp(): void
    {
        static::useTestingEnvironment();

        parent::setUp();
    }

    /**
     * Prepara la ejecución de Dusk.
     *
     * Requiere el binario de Chromedriver en vendor/laravel/dusk/bin
     * (`php artisan dusk:chrome-driver`). Si la app corre en Sail se reutiliza
     * el Chromedriver de Sail.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Crea el RemoteWebDriver de Chrome.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Deja el entorno de pruebas en $_ENV/$_SERVER y en putenv.
     *
     * Dotenv usa un repositorio inmutable (los valores ya presentes ganan a
     * `.env`) y Symfony Process hereda estas variables en los subprocesos.
     */
    private static function useTestingEnvironment(): void
    {
        foreach (self::TESTING_ENVIRONMENT as $key => $value) {
            putenv("{$key}={$value}");

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
