<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablece tu contraseña</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5; padding: 24px; color: #1f2937;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">Restablece tu contraseña</h1>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
            Hola{{ $name ? ', '.$name : '' }}. Recibimos una solicitud para restablecer la contraseña de tu cuenta en Tag q.
        </p>

        <p style="margin: 0 0 24px;">
            <a
                href="{{ $url }}"
                style="display: inline-block; background-color: #00052c; color: #ffffff; font-size: 14px; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 4px;"
            >Crear una contraseña nueva</a>
        </p>

        <p style="font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
            El enlace vence en {{ $expiresInMinutes }} minutos y solo se puede usar una vez.
        </p>

        <p style="font-size: 12px; line-height: 1.6; color: #6b7280; margin: 0;">
            Si no solicitaste este cambio, puedes ignorar este mensaje: tu contraseña actual seguirá funcionando.
        </p>
    </div>
</body>
</html>
