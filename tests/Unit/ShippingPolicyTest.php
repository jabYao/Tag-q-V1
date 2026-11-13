<?php

namespace Tests\Unit;

use App\Support\ShippingPolicy;
use Tests\TestCase;

/**
 * Política de envíos del negocio.
 *
 * Gratis desde el umbral configurado, abono de contraentrega por debajo del
 * umbral y costo estándar para el resto de pedidos.
 */
class ShippingPolicyTest extends TestCase
{
    public function test_el_envio_es_gratis_desde_el_umbral_para_cualquier_metodo(): void
    {
        $this->assertSame(0, ShippingPolicy::cost(500000, 'contraentrega'));
        $this->assertSame(0, ShippingPolicy::cost(500000, 'wompi'));
        $this->assertSame(0, ShippingPolicy::cost(900000, 'wompi'));
    }

    public function test_contraentrega_debajo_del_umbral_cobra_el_abono(): void
    {
        $this->assertSame(30000, ShippingPolicy::cost(499999, 'contraentrega'));
        $this->assertSame(30000, ShippingPolicy::advance(499999, 'contraentrega'));
        $this->assertSame(0, ShippingPolicy::advance(499999, 'wompi'));
    }

    public function test_el_envio_estandar_debajo_del_umbral_cobra_quince_mil(): void
    {
        $this->assertSame(15000, ShippingPolicy::cost(100000, 'wompi'));
        $this->assertSame(15000, ShippingPolicy::cost(100000, 'tarjeta'));
        $this->assertSame(0, ShippingPolicy::advance(100000, 'wompi'));
    }

    public function test_los_costos_se_leen_de_configuracion(): void
    {
        config()->set('legal.shipping.free_from', 1000000);
        config()->set('legal.shipping.cost', 12000);
        config()->set('legal.shipping.contraentrega_cost', 27000);

        $this->assertSame(0, ShippingPolicy::cost(1000000, 'wompi'));
        $this->assertSame(12000, ShippingPolicy::cost(999999, 'wompi'));
        $this->assertSame(27000, ShippingPolicy::cost(999999, 'contraentrega'));
    }
}
