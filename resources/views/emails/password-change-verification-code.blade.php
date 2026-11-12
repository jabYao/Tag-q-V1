<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tu código para cambiar tu contraseña</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; padding: 24px; color: #1f2937;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">Confirma el cambio de contraseña</h1>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
            Recibimos una solicitud para cambiar la contraseña de tu cuenta en Tag q. Usa este código para confirmarlo:
        </p>

        <p style="font-size: 32px; letter-spacing: 8px; font-weight: bold; text-align: center; margin: 24px 0; color: #111827;">
            {{ $code }}
        </p>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
            El código vence en {{ $expiresInMinutes }} minutos y solo se puede usar una vez.
        </p>

        <p style="font-size: 12px; line-height: 1.6; color: #6b7280; margin: 0;">
            Si no solicitaste este cambio, ignora este mensaje: tu contraseña actual sigue funcionando.
        </p>
    </div>
</body>
</html>
