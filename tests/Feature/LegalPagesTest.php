<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Páginas legales públicas del storefront.
 *
 * Las siete rutas sirven textos reales en español conforme al marco
 * colombiano, con fecha de actualización, versión, aviso de borrador mientras
 * falten datos del vendedor, enlace a la SIC y navegación entre documentos.
 *
 * El footer de escritorio conserva exactamente cuatro columnas directas:
 * marca, navegación, "Compra y envío" y "Legal" con los siete enlaces más el
 * enlace visible a la SIC. Los controles sociales deshabilitados viven dentro
 * de la columna de marca.
 */
class LegalPagesTest extends TestCase
{
    /**
     * Mapa canónico: ruta, URL, título visible y clave del documento.
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

    private function html(string $routeName): string
    {
        return $this->get(route($routeName))->assertOk()->getContent();
    }

    public function test_cada_pagina_legal_es_publica_y_sirve_contenido_real(): void
    {
        foreach ($this->legalPages() as [$name, $url, $title, $key]) {
            $response = $this->get($url)->assertOk();

            $response->assertSee('data-legal-page="'.$key.'"', false)
                ->assertSee('data-legal-title', false)
                ->assertSee($title)
                ->assertSee('data-legal-updated', false)
                ->assertSee('data-legal-body', false)
                ->assertDontSee('data-legal-pending', false)
                ->assertSee('data-storefront-nav', false)
                ->assertSee('data-storefront-footer', false);

            $this->assertSame(url($url), route($name), "La ruta {$name} apunta a {$url}");
        }
    }

    public function test_las_paginas_legales_enlazan_entre_si_ya_la_sic(): void
    {
        $html = $this->html('legal.privacy');

        foreach ($this->legalPages() as [$name, $url, $title, $key]) {
            $this->assertStringContainsString('href="'.route($name).'"', $html, "La página legal enlaza {$name}");
            $this->assertStringContainsString($title, $html, "La navegación legal muestra {$title}");
        }

        $this->assertStringContainsString('data-legal-sic', $html, 'La página legal expone el enlace a la SIC');
        $this->assertStringContainsString('href="'.config('legal.sic.url').'"', $html, 'El enlace apunta a la SIC configurada');
    }

    public function test_el_borrador_se_avisa_mientras_falten_datos_del_vendedor(): void
    {
        $html = $this->html('legal.privacy');

        $this->assertStringContainsString('data-legal-draft', $html);
        $this->assertStringContainsString('Borrador', $html);
        $this->assertStringContainsString('revisión por un abogado', $html);
    }

    public function test_el_borrador_desaparece_con_datos_completos_y_revision_aprobada(): void
    {
        config()->set('legal.draft', false);
        config()->set('legal.seller.name', 'Vendedor de Prueba');
        config()->set('legal.seller.document', '900123456-7');
        config()->set('legal.seller.address', 'Calle 1 # 2-3');
        config()->set('legal.seller.city', 'Bogotá');
        config()->set('legal.seller.email', 'ventas@example.com');

        $html = $this->html('legal.privacy');

        $this->assertStringNotContainsString('data-legal-draft', $html);
        $this->assertStringContainsString('Vendedor de Prueba', $html);
        $this->assertStringContainsString('Calle 1 # 2-3, Bogotá', $html);
    }

    public function test_el_domicilio_se_publica_a_nivel_municipio_cuando_no_hay_direccion(): void
    {
        config()->set('legal.draft', false);
        config()->set('legal.seller.name', 'Vendedor de Prueba');
        config()->set('legal.seller.document', '900123456-7');
        config()->set('legal.seller.address', null);
        config()->set('legal.seller.city', 'Dibulla, La Guajira');
        config()->set('legal.seller.email', 'ventas@example.com');

        $privacy = $this->html('legal.privacy');

        $this->assertStringContainsString('Dibulla, La Guajira', $privacy);
        $this->assertStringNotContainsString('[Completar: dirección del domicilio]', $privacy);
        $this->assertStringNotContainsString('data-legal-draft', $privacy);

        $contact = $this->html('legal.contact');

        $this->assertStringContainsString('Domicilio: Dibulla, La Guajira', $contact);
    }

    public function test_privacidad_cubre_contenido_minimo_derechos_y_plazos(): void
    {
        $html = $this->html('legal.privacy');

        foreach ([
            'Ley 1581 de 2012',
            'Decreto 1074 de 2015',
            '10 días hábiles',
            '15 días hábiles',
            'derecho a conocer',
            'Superintendencia de Industria y Comercio',
            'transferencias internacionales',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "Privacidad incluye: {$needle}");
        }
    }

    public function test_terminos_cubre_retracto_garantia_y_acuse_de_recibo(): void
    {
        $html = $this->html('legal.terms');

        foreach ([
            'Ley 1480 de 2011',
            'Ley 2439 de 2024',
            'cinco (5) días hábiles',
            'un (1) año',
            'acuse de recibo',
            'reversión',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "Términos incluye: {$needle}");
        }
    }

    public function test_cookies_inventaria_solo_las_cookies_reales(): void
    {
        $html = $this->html('legal.cookies');

        foreach (['tag-q-session', 'XSRF-TOKEN', 'Wompi', 'No utilizamos cookies de analítica ni de publicidad'] as $needle) {
            $this->assertStringContainsString($needle, $html, "Cookies incluye: {$needle}");
        }
    }

    public function test_devoluciones_envios_pagos_y_contacto_cubren_los_plazos_legales(): void
    {
        $returns = $this->html('legal.returns');
        foreach (['retracto', 'garantía', 'quince (15) días calendario'] as $needle) {
            $this->assertStringContainsString($needle, $returns, "Devoluciones incluye: {$needle}");
        }

        $shipping = $this->html('legal.shipping');
        foreach (['treinta (30) días calendario', 'Contraentrega'] as $needle) {
            $this->assertStringContainsString($needle, $shipping, "Envíos incluye: {$needle}");
        }

        $payments = $this->html('legal.payments');
        foreach (['Wompi', 'contraentrega', 'No almacenamos'] as $needle) {
            $this->assertStringContainsString($needle, $payments, "Pagos incluye: {$needle}");
        }

        $contact = $this->html('legal.contact');
        foreach (['PQRS', 'radicado', 'Superintendencia de Industria y Comercio'] as $needle) {
            $this->assertStringContainsString($needle, $contact, "Contacto incluye: {$needle}");
        }
    }

    public function test_el_footer_enlaza_las_siete_paginas_legales_y_la_sic(): void
    {
        $html = $this->get(route('legal.privacy'))->assertOk()->getContent();

        foreach ($this->legalPages() as [$name, $url, $title, $key]) {
            $this->assertStringContainsString('href="'.route($name).'"', $html, "El footer enlaza {$name}");
            $this->assertStringContainsString($title, $html, "El footer muestra el enlace {$title}");
        }

        $this->assertStringContainsString('data-footer-link="sic"', $html);
    }

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

        $this->assertSame(1, substr_count($html, 'data-footer-block="social"'));
        $this->assertStringNotContainsString('Muy pronto compartiremos aquí nuestras redes sociales y nuestro WhatsApp.', $html);
        $this->assertStringNotContainsString('>Redes y WhatsApp<', $html);

        $this->assertSame(3, substr_count($html, 'data-footer-social='), 'Solo quedan Instagram, Facebook y WhatsApp');
        foreach (['instagram', 'facebook', 'whatsapp'] as $social) {
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

        $brandPosition = strpos($html, 'data-footer-block="brand"');
        $socialPosition = strpos($html, 'data-footer-block="social"');
        $navigationPosition = strpos($html, 'data-footer-block="navigation"');
        $this->assertTrue($brandPosition < $socialPosition && $socialPosition < $navigationPosition, 'Los controles sociales viven dentro de la columna de marca, antes de la navegación');
    }

    public function test_el_footer_conserva_la_marca_y_el_copyright(): void
    {
        $html = $this->get(route('legal.cookies'))->assertOk()->getContent();

        $this->assertStringContainsString('data-footer-brand', $html);
        $this->assertStringContainsString('data-footer-brand-mark', $html);
        $this->assertStringContainsString('h-8 w-auto shrink-0"', $html);
        $this->assertStringContainsString('font-jost text-[40px]', $html);
        $this->assertStringNotContainsString('brightness-0 invert', $html);
        $this->assertStringContainsString('Tag Q', $html);
        $this->assertStringContainsString('&copy; '.date('Y').' Tag Q. Todos los derechos reservados.', $html);
    }
}
