<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Páginas legales públicas del storefront.
 *
 * Las siete rutas legales existen como destinos reales y navegables, pero su
 * contenido todavía no está aprobado: cada una muestra solo su título y un
 * aviso explícito de que el texto está pendiente. No se inventan políticas,
 * direcciones, correos ni plazos. Las páginas usan el layout público
 * `layouts.storefront`, así que heredan navbar, footer, Livewire y assets del
 * shell compartido.
 *
 * El footer de escritorio conserva exactamente cuatro columnas directas:
 * marca, navegación, "Compra y envío" y la nueva columna "Legal" con los
 * siete enlaces. Los cinco controles sociales deshabilitados viven dentro de
 * la columna de marca, sin encabezado ni copy visibles.
 *
 * No toca base de datos: son vistas estáticas servidas con `Route::view`, sin
 * controlador ni paquete nuevos.
 */
class LegalPagesTest extends TestCase
{
    /**
     * Mapa canónico de las páginas legales: nombre de ruta, URL y título
     * visible que debe conservarse.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function legalPages(): array
    {
        return [
            ['legal.privacy', '/politica-de-privacidad', 'Política de privacidad', 'privacy'],
            ['legal.terms', '/terminos-y-condiciones', 'Términos y condiciones', 'terms'],
            ['legal.cookies', '/politica-de-cookies', 'Política de cookies', 'cookies'],
            ['legal.returns', '/cambios-y-devoluciones', 'Cambios y devoluciones', 'returns'],
            ['legal.shipping', '/envios-y-entregas', 'Envíos y entregas', 'shipping'],
            ['legal.payments', '/medios-de-pago', 'Medios de pago', 'payments'],
            ['legal.contact', '/contacto', 'Contacto', 'contact'],
        ];
    }

    /**
     * Cada ruta legal responde 200, conserva su título y declara que el
     * contenido está pendiente, dentro del shell público del storefront.
     */
    public function test_cada_pagina_legal_es_publica_y_avisa_que_el_contenido_esta_pendiente(): void
    {
        foreach ($this->legalPages() as [$name, $url, $title, $key]) {
            $response = $this->get($url)->assertOk();

            $response->assertSee('data-legal-page="'.$key.'"', false)
                ->assertSee('data-legal-title', false)
                ->assertSee($title)
                ->assertSee('data-legal-pending', false)
                ->assertSee('El contenido de esta página está pendiente.')
                ->assertSee('data-storefront-nav', false)
                ->assertSee('data-storefront-footer', false);

            $this->assertSame(url($url), route($name), "La ruta {$name} apunta a {$url}");
        }
    }

    /**
     * El footer expone los siete enlaces legales como rutas reales.
     */
    public function test_el_footer_enlaza_las_siete_paginas_legales(): void
    {
        $html = $this->get(route('legal.privacy'))->assertOk()->getContent();

        foreach ($this->legalPages() as [$name, $url, $title, $key]) {
            $this->assertStringContainsString('href="'.route($name).'"', $html, "El footer enlaza {$name}");
            $this->assertStringContainsString($title, $html, "El footer muestra el enlace {$title}");
        }
    }

    /**
     * El footer conserva exactamente cuatro columnas directas de escritorio
     * —marca, navegación, compra y envío, legal— y mantiene los cinco
     * controles sociales deshabilitados dentro de la columna de marca, sin
     * rótulo ni copy visibles.
     */
    public function test_el_footer_mantiene_cuatro_columnas_y_conserva_redes_y_whatsapp(): void
    {
        $html = $this->get(route('legal.terms'))->assertOk()->getContent();

        foreach (['brand', 'navigation', 'service', 'legal'] as $block) {
            $this->assertSame(
                1,
                substr_count($html, 'data-footer-block="'.$block.'"'),
                "El footer tiene una sola columna {$block}"
            );
        }

        // Los controles sociales siguen presentes como sub-bloque de la
        // marca, sin encabezado visible ni copy descriptivo.
        $this->assertSame(1, substr_count($html, 'data-footer-block="social"'));
        $this->assertStringNotContainsString('Muy pronto compartiremos aquí nuestras redes sociales y nuestro WhatsApp.', $html);
        $this->assertStringNotContainsString('>Redes y WhatsApp<', $html);

        $this->assertSame(3, substr_count($html, 'data-footer-social='), 'Solo quedan Instagram, Facebook y WhatsApp');
        foreach (['instagram', 'facebook', 'whatsapp'] as $social) {
            // Siguen siendo botones deshabilitados, no enlaces con URL inventada.
            $this->assertMatchesRegularExpression(
                '#<button[^>]*disabled[^>]*data-footer-social="'.$social.'"#s',
                $html,
                "Se conserva el control deshabilitado {$social}"
            );
        }

        foreach (['Instagram', 'Facebook', 'WhatsApp'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'<', $html, 'La red '.$label.' se muestra solo como icono');
        }
        $this->assertStringNotContainsString('data-footer-social="tiktok"', $html);
        $this->assertStringNotContainsString('data-footer-social="youtube"', $html);
        $socialStart = strpos($html, 'data-footer-block="social"');
        $socialEnd = strpos($html, '</section>', $socialStart);
        $socialMarkup = substr($html, $socialStart, $socialEnd - $socialStart);
        $this->assertStringNotContainsString('border-white/20', $socialMarkup);
        $this->assertStringContainsString('h-5 w-5', $socialMarkup);
        $this->assertStringContainsString('data-whatsapp-icon', $socialMarkup);
        $this->assertStringContainsString('data-whatsapp-handset', $socialMarkup);

        // El sub-bloque social vive dentro de la columna de marca, antes de
        // la navegación, la compra y envío y la columna legal.
        $brandPosition = strpos($html, 'data-footer-block="brand"');
        $socialPosition = strpos($html, 'data-footer-block="social"');
        $navigationPosition = strpos($html, 'data-footer-block="navigation"');
        $this->assertTrue($brandPosition < $socialPosition && $socialPosition < $navigationPosition, 'Los controles sociales viven dentro de la columna de marca, antes de la navegación');
    }

    /**
     * La marca y el copyright del footer no cambian con la columna legal nueva.
     */
    public function test_el_footer_conserva_la_marca_y_el_copyright(): void
    {
        $html = $this->get(route('legal.cookies'))->assertOk()->getContent();

        $this->assertStringContainsString('data-footer-brand', $html);
        $this->assertStringContainsString('data-footer-brand-mark', $html);
        $this->assertStringContainsString('h-8 w-auto shrink-0 brightness-0 invert', $html);
        $this->assertStringContainsString('font-jost text-[40px]', $html);
        $this->assertStringContainsString('brightness-0 invert', $html);
        $this->assertStringContainsString('Tag Q', $html);
        $this->assertStringContainsString('&copy; '.date('Y').' Tag Q. Todos los derechos reservados.', $html);
    }
}
