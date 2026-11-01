<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

/**
 * Precio de oferta del producto: `precio` es el precio normal (el que se
 * tacha) y `precio_oferta` es el precio final que se cobra. La insignia
 * `N% OFF` se deriva de ambos para que nunca contradiga el cobro.
 */
class ProductPricingTest extends TestCase
{
    public function test_sin_oferta_el_precio_final_es_el_precio_normal(): void
    {
        $product = new Product(['precio' => 189900]);

        $this->assertSame(189900, $product->precioFinal());
        $this->assertFalse($product->tieneDescuento());
        $this->assertNull($product->descuentoPorcentaje());
    }

    public function test_una_oferta_menor_es_el_precio_final_y_calcula_el_porcentaje(): void
    {
        $product = new Product(['precio' => 189900, 'precio_oferta' => 132930]);

        $this->assertTrue($product->tieneDescuento());
        $this->assertSame(132930, $product->precioFinal());
        $this->assertSame(30, $product->descuentoPorcentaje());
    }

    public function test_el_porcentaje_se_redondea_al_entero_mas_cercano(): void
    {
        $product = new Product(['precio' => 100000, 'precio_oferta' => 66667]);

        $this->assertSame(33, $product->descuentoPorcentaje());
    }

    /**
     * Una oferta igual, mayor, cero o negativa no es un descuento real: no se
     * anuncia y nunca reemplaza al precio normal.
     */
    public function test_ofertas_invalidas_no_reemplazan_el_precio_normal(): void
    {
        foreach ([[189900, 189900], [189900, 250000], [189900, 0], [189900, -1000]] as [$precio, $oferta]) {
            $product = new Product(['precio' => $precio, 'precio_oferta' => $oferta]);

            $this->assertFalse($product->tieneDescuento(), "Oferta {$oferta} no debe contar como descuento");
            $this->assertSame($precio, $product->precioFinal());
            $this->assertNull($product->descuentoPorcentaje());
        }
    }

    public function test_un_precio_normal_en_cero_no_anuncia_descuento(): void
    {
        $product = new Product(['precio' => 0, 'precio_oferta' => 0]);

        $this->assertFalse($product->tieneDescuento());
        $this->assertSame(0, $product->precioFinal());
        $this->assertNull($product->descuentoPorcentaje());
    }
}
