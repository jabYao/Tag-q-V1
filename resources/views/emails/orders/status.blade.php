<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $eventLabel }} — Pedido {{ $orderCode }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; padding: 24px; color: #1f2937;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">{{ $eventLabel }}</h1>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
            Pedido <strong>{{ $orderCode }}</strong>
        </p>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
            Estado actual: <strong>{{ $statusLabel }}</strong>
        </p>

        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0 0 24px;">
            <thead>
                <tr style="text-align: left; background-color: #f3f4f6;">
                    <th style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Producto</th>
                    <th style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Cantidad</th>
                    <th style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Precio unitario COP</th>
                    <th style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Subtotal COP</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $item['name'] }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $item['quantity'] }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="padding: 12px; text-align: center; color: #6b7280;">Sin items</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 8px; font-weight: bold;">Total</td>
                    <td style="padding: 8px; font-weight: bold;">{{ number_format($total, 0, ',', '.') }} COP</td>
                </tr>
            </tfoot>
        </table>

        @if (! empty($orderUrl))
            <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
                <a href="{{ $orderUrl }}" style="color: #1d4ed8;">Ver el detalle de tu pedido</a>
            </p>
        @endif

        <p style="font-size: 12px; line-height: 1.6; color: #6b7280; margin: 0;">
            Este mensaje se envió automáticamente por una actualización de tu pedido en Tag q.
        </p>
    </div>
</body>
</html>
