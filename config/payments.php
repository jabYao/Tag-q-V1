<?php

/*
|--------------------------------------------------------------------------
| Instrucciones de pago por transferencia manual
|--------------------------------------------------------------------------
|
| El abono de envío de los pedidos contraentrega se cobra por transferencia
| manual (Nequi o cuenta bancaria) y el administrador confirma el ingreso.
| Los datos de la cuenta se configuran por entorno: nunca se escriben en el
| código. Si no hay datos configurados, la tienda pide escribir para recibir
| los datos en lugar de inventar una cuenta.
|
*/

return [
    'transfer' => [
        'nequi' => env('PAYMENT_TRANSFER_NEQUI'),
        'bank' => env('PAYMENT_TRANSFER_BANK'),
        'account_type' => env('PAYMENT_TRANSFER_ACCOUNT_TYPE'),
        'account' => env('PAYMENT_TRANSFER_ACCOUNT'),
        'holder' => env('PAYMENT_TRANSFER_HOLDER'),
        'document' => env('PAYMENT_TRANSFER_DOCUMENT'),
    ],
];
