<?php

namespace Tests\Unit;

use App\Support\CloudinaryUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Transformaciones de entrega de Cloudinary.
 *
 * La tarjeta de producto pide `e_trim` para quitar el margen blanco de la foto
 * de estudio; el resto de vistas (galería y detalle) conserva la imagen
 * completa. Estas pruebas fijan ese contrato sin base de datos ni HTTP.
 */
class CloudinaryUrlTest extends TestCase
{
    private const BASE = 'https://res.cloudinary.com/dg6iut6sl/image/upload/';

    public function test_webp_card_inserta_recorte_formato_y_calidad_antes_de_la_version(): void
    {
        $url = self::BASE.'v1780611034/tag-q/producto/reloj.jpg';

        $this->assertSame(
            self::BASE.'e_trim,f_webp,q_auto/v1780611034/tag-q/producto/reloj.jpg',
            CloudinaryUrl::webpCard($url),
        );
    }

    public function test_webp_card_agrega_el_recorte_a_una_url_que_ya_tiene_formato_y_calidad(): void
    {
        $url = self::BASE.'f_webp,q_auto/v1/tag-q/producto/reloj.jpg';

        $this->assertSame(
            self::BASE.'f_webp,q_auto,e_trim/v1/tag-q/producto/reloj.jpg',
            CloudinaryUrl::webpCard($url),
        );
    }

    public function test_webp_card_es_idempotente(): void
    {
        $url = self::BASE.'v1/tag-q/producto/reloj.jpg';

        $once = CloudinaryUrl::webpCard($url);

        $this->assertSame($once, CloudinaryUrl::webpCard($once));
    }

    public function test_webp_card_conserva_las_transformaciones_existentes(): void
    {
        $url = self::BASE.'w_800,c_fill/v1/tag-q/producto/reloj.jpg';

        $this->assertSame(
            self::BASE.'w_800,c_fill,e_trim,f_webp,q_auto/v1/tag-q/producto/reloj.jpg',
            CloudinaryUrl::webpCard($url),
        );
    }

    public function test_webp_no_agrega_el_recorte(): void
    {
        $url = self::BASE.'v1/tag-q/producto/reloj.jpg';

        $this->assertSame(
            self::BASE.'f_webp,q_auto/v1/tag-q/producto/reloj.jpg',
            CloudinaryUrl::webp($url),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function urlsSinTransformar(): array
    {
        return [
            'otro host' => ['https://cdn.example.com/tag-q/producto/reloj.jpg'],
            'ruta que no es de imagen' => ['https://res.cloudinary.com/dg6iut6sl/raw/upload/v1/documento.pdf'],
            'sin segmento de subida' => ['https://res.cloudinary.com/dg6iut6sl/image/fetch/v1/reloj.jpg'],
        ];
    }

    #[DataProvider('urlsSinTransformar')]
    public function test_webp_card_deja_intactas_las_urls_que_no_son_de_entrega_de_imagen(string $url): void
    {
        $this->assertSame($url, CloudinaryUrl::webpCard($url));
    }

    public function test_webp_card_admite_valores_vacios(): void
    {
        $this->assertNull(CloudinaryUrl::webpCard(null));
        $this->assertSame('', CloudinaryUrl::webpCard(''));
    }
}
