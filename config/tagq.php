<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial (producción)
    |--------------------------------------------------------------------------
    |
    | Las credenciales se inyectan por variables de entorno en Railway.
    | Si faltan, `ProductionSeeder` no crea ningún admin (nunca deja una
    | contraseña por defecto). Nunca versionar estos valores.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorías base del catálogo
    |--------------------------------------------------------------------------
    */

    'categories' => [
        ['name' => 'Dama', 'slug' => 'dama'],
        ['name' => 'Caballero', 'slug' => 'caballero'],
        ['name' => 'Niños', 'slug' => 'ninos'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subcategorías fijas del catálogo
    |--------------------------------------------------------------------------
    |
    | Taxonomía cerrada: la clave es el slug canónico que se guarda en
    | `products.subcategoria` y el valor es la etiqueta para la UI. Es la
    | fuente única para validación, formulario admin y filtros del catálogo.
    */

    'subcategories' => [
        'elegante' => 'Elegante',
        'casual' => 'Casual',
        'deportivo' => 'Deportivo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Umbral operativo de stock bajo
    |--------------------------------------------------------------------------
    |
    | El negocio todavía no confirmó un umbral. Se usa 3 como corte operativo
    | visible y marcado como provisional en el panel; no debe presentarse como
    | una política definitiva.
    */

    'low_stock_threshold' => 3,

];
