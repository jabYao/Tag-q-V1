<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\WompiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contrato HTTP de reembolsos de Wompi.
 *
 * El sandbox responde hoy con el contrato legacy (201 + data.id + PENDING)
 * aunque se envíen los campos V2; el parser debe aceptar ambos shapes para no
 * quedar atado a una versión. No hay base de datos ni pago real.
 */
class WompiRefundPayloadTest extends TestCase
{
    private const API_URL = 'https://sandbox.wompi.co/v1';

    private const REFUND_URL = self::API_URL.'/refunds';

    private const PRIVATE_KEY = 'prv_test_fake_private_key';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wompi.api_url', self::API_URL);
        config()->set('services.wompi.private_key', self::PRIVATE_KEY);
        config()->set('services.wompi.merchant_id', '2082714');
        config()->set('services.wompi.region', 'CO');
        config()->set('services.wompi.refund_test_scenario', null);
    }

    private function order(): Order
    {
        return new Order([
            'code' => 'TAG-ABC123',
            'total' => 519900,
            'wompi_transaction_id' => 'txn_abc123',
        ]);
    }

    public function test_payload_envia_contrato_legacy_y_campos_v2(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'data' => ['id' => 32961, 'status' => 'PENDING'],
            ], 201),
        ]);

        $result = app(WompiService::class)->requestRefund($this->order(), 'refund-key-1', 'Cliente solicita devolución');

        Http::assertSent(function (ClientRequest $request): bool {
            $data = $request->data();

            return $request->url() === self::REFUND_URL
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer '.self::PRIVATE_KEY)
                && $data['transaction_id'] === 'txn_abc123'
                && $data['amount_in_cents'] === 51990000
                && $data['reason'] === 'Cliente solicita devolución'
                && $data['reference'] === 'TAG-ABC123'
                && $data['idempotency_key'] === 'refund-key-1'
                && $data['merchant_id'] === '2082714'
                && $data['region'] === 'CO'
                && $data['currency'] === 'COP'
                && $data['fee_in_cents'] === 0
                && $data['fee_vat_in_cents'] === 0
                && $data['total_freeze_amount'] === 51990000
                && $data['refund_type'] === 'TOTAL'
                && $data['original_amount_in_cents'] === 51990000
                && $data['references'] === ['TAG-ABC123'];
        });

        $this->assertTrue($result->accepted);
        $this->assertSame(201, $result->httpStatus);
        $this->assertSame('32961', $result->id, 'El id numérico legacy se normaliza a string');
        $this->assertSame('PENDING', $result->status);
        $this->assertTrue($result->isPending());
    }

    public function test_parsea_contrato_v2_con_refund_id_de_nivel_superior(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'refund_id' => 'COa7Bk9m1',
                'status' => 'PENDING',
                'flow_version' => 'v2',
            ], 202),
        ]);

        $result = app(WompiService::class)->requestRefund($this->order(), 'refund-key-2');

        $this->assertTrue($result->accepted);
        $this->assertSame(202, $result->httpStatus);
        $this->assertSame('COa7Bk9m1', $result->id);
        $this->assertSame('PENDING', $result->status);
    }

    public function test_parsea_409_de_idempotencia_como_reembolso_existente(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'refund_id' => 'COexisting1',
                'status' => 'APPROVED',
            ], 409),
        ]);

        $result = app(WompiService::class)->requestRefund($this->order(), 'refund-key-3');

        $this->assertTrue($result->accepted, 'Un 409 de idempotencia no es un fallo: el reembolso ya existe');
        $this->assertSame(409, $result->httpStatus);
        $this->assertSame('COexisting1', $result->id);
        $this->assertSame('APPROVED', $result->status);
    }

    public function test_parsea_error_de_validacion_de_wompi(): void
    {
        Http::fake([
            self::REFUND_URL => Http::response([
                'error' => [
                    'type' => 'INPUT_VALIDATION_ERROR',
                    'messages' => [
                        'amount_in_cents' => ['La suma de los reembolsos de una transacción no pueden superar la cantidad de dicha transacción'],
                    ],
                ],
            ], 422),
        ]);

        $result = app(WompiService::class)->requestRefund($this->order(), 'refund-key-4');

        $this->assertFalse($result->accepted);
        $this->assertSame(422, $result->httpStatus);
        $this->assertNull($result->id);
        $this->assertNull($result->status);
        $this->assertSame('INPUT_VALIDATION_ERROR', $result->errorCode);
        $this->assertStringContainsString('La suma de los reembolsos', (string) $result->errorMessage);
    }

    public function test_fallo_de_red_devuelve_un_resultado_ambiguo(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $result = app(WompiService::class)->requestRefund($this->order(), 'refund-key-5');

        $this->assertFalse($result->accepted);
        $this->assertSame(0, $result->httpStatus);
        $this->assertTrue($result->networkError);
        $this->assertSame('NETWORK_ERROR', $result->errorCode);
    }

    public function test_consulta_un_reembolso_por_id(): void
    {
        Http::fake([
            self::REFUND_URL.'/32961' => Http::response([
                'data' => ['id' => 32961, 'status' => 'APPROVED', 'amount_in_cents' => 51990000],
            ], 200),
        ]);

        $result = app(WompiService::class)->fetchRefund('32961');

        $this->assertTrue($result->accepted);
        $this->assertSame('32961', $result->id);
        $this->assertSame('APPROVED', $result->status);
    }

    public function test_lee_el_ultimo_reembolso_de_la_transaccion(): void
    {
        Http::fake([
            self::API_URL.'/transactions/txn_abc123' => Http::response([
                'data' => [
                    'id' => 'txn_abc123',
                    'status' => 'APPROVED',
                    'refunds' => [
                        ['status' => 'DECLINED', 'amount_in_cents' => 51990000],
                        ['status' => 'APPROVED', 'amount_in_cents' => 51990000],
                    ],
                ],
            ], 200),
        ]);

        $result = app(WompiService::class)->refundFromTransaction('txn_abc123');

        $this->assertNotNull($result);
        $this->assertSame('APPROVED', $result->status);
        $this->assertTrue($result->isApproved());
    }

    public function test_transaccion_sin_reembolsos_devuelve_null(): void
    {
        Http::fake([
            self::API_URL.'/transactions/txn_sin_refund' => Http::response([
                'data' => ['id' => 'txn_sin_refund', 'status' => 'APPROVED', 'refunds' => null],
            ], 200),
        ]);

        $this->assertNull(app(WompiService::class)->refundFromTransaction('txn_sin_refund'));
    }
}
