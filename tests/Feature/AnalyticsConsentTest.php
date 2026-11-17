<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consentimiento de analítica (Google Analytics 4).
 *
 * La regla dura es el opt-in: antes de que el visitante acepte, el storefront no
 * incluye el cargador de Google ni ejecuta `gtag`. El servidor decide con la
 * cookie `tagq_analytics_consent`, que escribe el banner desde el navegador y por
 * eso queda excluida del cifrado de cookies de Laravel; los tests la envían con
 * `withUnencryptedCookie` para reproducir ese origen plano. Si la exclusión se
 * pierde, `EncryptCookies` la anula y el aceptar deja de funcionar: el test de
 * aceptación es el que lo detecta.
 *
 * El panel de administración nunca mide, con o sin consentimiento.
 */
class AnalyticsConsentTest extends TestCase
{
    use RefreshDatabase;

    private const MEASUREMENT_ID = 'G-TEST123456';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.analytics_id', self::MEASUREMENT_ID);
    }

    public function test_sin_consentimiento_el_storefront_no_carga_analitica(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('gtag/js', false);
        $response->assertSee('data-analytics-consent', false);
    }

    public function test_aceptar_carga_el_cargador_y_retira_el_aviso(): void
    {
        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk();

        $response->assertSee('googletagmanager.com/gtag/js?id='.self::MEASUREMENT_ID, false);
        $response->assertSee("gtag('config'", false);
        $response->assertSee(self::MEASUREMENT_ID, false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_rechazar_no_carga_nada_y_no_vuelve_a_preguntar(): void
    {
        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'rejected')
            ->get('/')
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee(self::MEASUREMENT_ID, false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_sin_id_de_medicion_no_hay_aviso_ni_analitica(): void
    {
        config()->set('services.google.analytics_id', null);

        $response = $this->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get('/')
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_el_panel_de_administracion_nunca_carga_analitica(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withUnencryptedCookie('tagq_analytics_consent', 'accepted')
            ->get(route('admin.products.index'))
            ->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('data-analytics-consent', false);
    }

    public function test_el_aviso_ofrece_aceptar_y_rechazar_y_enlaza_la_politica(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $start = strpos($html, 'data-analytics-consent');
        $this->assertNotFalse($start, 'El aviso de consentimiento se muestra sin decisión previa');

        $end = strpos($html, '</section>', $start);
        $banner = substr($html, $start, $end - $start);

        $this->assertStringContainsString('data-analytics-accept', $banner);
        $this->assertStringContainsString('data-analytics-reject', $banner);
        $this->assertStringContainsString('Aceptar', $banner);
        $this->assertStringContainsString('Rechazar', $banner);
        $this->assertStringContainsString('href="'.route('legal.cookies').'"', $banner);
    }
}
