<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tu código de verificación</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; padding: 24px; color: #1f2937;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">Verifica tu correo</h1>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
            Gracias por registrarte en Tag q. Usa el siguiente código para verificar tu cuenta:
        </p>

        <p style="font-size: 32px; letter-spacing: 8px; font-weight: bold; text-align: center; margin: 24px 0; color: #111827;">
            {{ $code }}
        </p>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
            El código vence en {{ $expiresInMinutes }} minutos.
        </p>

        <p style="font-size: 12px; line-height: 1.6; color: #6b7280; margin: 0;">
            Si no solicitaste este registro, puedes ignorar este mensaje.
        </p>
    </div>
</body>
</html>
