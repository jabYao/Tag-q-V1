<?php

/*
|--------------------------------------------------------------------------
| Datos legales del vendedor y políticas publicadas
|--------------------------------------------------------------------------
|
| Ningún dato personal del vendedor se escribe en el código: nombre,
| documento, ciudad, correos, horario y transportadora se inyectan por
| variables de entorno. La dirección física es opcional: si el vendedor no
| tiene oficina, el domicilio se publica a nivel municipio (`city`). Mientras
| falten datos obligatorios, `App\Support\Legal` devuelve marcadores visibles
| y las páginas muestran el aviso de borrador.
|
| `LEGAL_DRAFT` debe permanecer en `true` hasta que un abogado apruebe los
| textos y todos los datos del vendedor estén completos.
|
*/

return [

    'draft' => (bool) env('LEGAL_DRAFT', true),

    'updated_at' => env('LEGAL_UPDATED_AT', '13 de noviembre de 2026'),

    /*
    | Versión de cada documento. Se guarda junto al consentimiento para probar
    | qué texto aceptó el titular. Cambiar un texto exige cambiar su versión.
    */
    'versions' => [
        'terms' => env('LEGAL_TERMS_VERSION', '2026-11-13'),
        'privacy' => env('LEGAL_PRIVACY_VERSION', '2026-11-13'),
        'cookies' => env('LEGAL_COOKIES_VERSION', '2026-11-12'),
        'returns' => env('LEGAL_RETURNS_VERSION', '2026-11-12'),
        'shipping' => env('LEGAL_SHIPPING_VERSION', '2026-11-12'),
        'payments' => env('LEGAL_PAYMENTS_VERSION', '2026-11-12'),
        'contact' => env('LEGAL_CONTACT_VERSION', '2026-11-13'),
    ],

    'seller' => [
        'name' => env('LEGAL_SELLER_NAME'),
        'trade_name' => env('LEGAL_SELLER_TRADE_NAME', 'Tag Q'),
        'document' => env('LEGAL_SELLER_DOCUMENT'),
        // Opcional: sin oficina, el domicilio se publica solo con `city`.
        'address' => env('LEGAL_SELLER_ADDRESS'),
        'city' => env('LEGAL_SELLER_CITY'),
        'email' => env('LEGAL_SELLER_EMAIL'),
        'privacy_email' => env('LEGAL_PRIVACY_EMAIL'),
        'pqrs_email' => env('LEGAL_PQRS_EMAIL'),
        'phone' => env('LEGAL_SELLER_PHONE', '+57 311 305 9507'),
        'hours' => env('LEGAL_SELLER_HOURS'),
        'website' => env('LEGAL_SELLER_WEBSITE'),
    ],

    /*
    | Plazo máximo de entrega. La Ley 2439 de 2024 fija treinta (30) días
    | calendario como tope legal; el negocio puede informar uno menor.
    */
    'delivery' => [
        'max_days' => (int) env('LEGAL_DELIVERY_MAX_DAYS', 30),
    ],

    'shipping' => [
        'cost' => (int) env('LEGAL_SHIPPING_COST', 0),
        'carrier' => env('LEGAL_SHIPPING_CARRIER'),
    ],

    'retract' => [
        'business_days' => 5,
        'refund_calendar_days' => 15,
    ],

    'warranty' => [
        'months' => 12,
    ],

    'pqrs' => [
        'response_business_days' => 15,
        'extension_business_days' => 8,
    ],

    'data_protection' => [
        'query_business_days' => 10,
        'query_extension_business_days' => 5,
        'claim_business_days' => 15,
        'claim_extension_business_days' => 8,
    ],

    'sic' => [
        'url' => env('LEGAL_SIC_URL', 'https://www.sic.gov.co'),
    ],

];
