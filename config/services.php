<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Correo transaccional de pedidos. `override` redirige todos los correos de
    // pedido a un único buzón cuando se define y es un email válido; sirve para
    // QA/staging sin tocar el código. Vacío o inválido se ignora por completo.
    'transactional_mail' => [
        'override' => env('TRANSACTIONAL_MAIL_OVERRIDE'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wompi' => [
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'events_secret' => env('WOMPI_EVENTS_SECRET'),
        'api_url' => env('WOMPI_API_URL', 'https://sandbox.wompi.co/v1'),

        // Escenario de prueba opcional que Wompi acepta en el sandbox para
        // forzar el resultado del reembolso. Solo se envía cuando la api_url
        // apunta al sandbox; en producción se ignora aunque la variable exista.
        'refund_test_scenario' => env('WOMPI_REFUND_TEST_SCENARIO'),

        // Minutos que se espera el pago de un pedido Wompi antes de cerrarlo y
        // liberar su stock cuando el webhook nunca llega (ver
        // orders:expire-wompi-pending).
        'pending_ttl_minutes' => env('WOMPI_PENDING_TTL_MINUTES', 30),
    ],

];
