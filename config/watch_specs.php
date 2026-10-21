<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taxonomías estructuradas
    |--------------------------------------------------------------------------
    |
    | La clave es el slug canónico que se guarda en la base de datos y el
    | valor es la etiqueta que ve el usuario. Son la fuente única para la
    | validación, el formulario administrativo, la ficha y los filtros.
    |
    */

    'movement_types' => [
        'cuarzo' => 'Cuarzo',
        'automatico' => 'Automático',
        'mecanico_manual' => 'Mecánico manual',
        'cuarzo_solar' => 'Cuarzo solar',
        'kinetic' => 'Kinetic',
        'spring_drive' => 'Spring Drive',
        'hibrido_smart' => 'Híbrido / Smart',
        'digital' => 'Digital',
    ],

    'case_shapes' => [
        'redonda' => 'Redonda',
        'cojin' => 'Cojín',
        'tonneau' => 'Tonneau',
        'cuadrada' => 'Cuadrada',
        'rectangular' => 'Rectangular',
        'octogonal' => 'Octogonal',
        'asimetrica' => 'Asimétrica',
    ],

    'crystal_materials' => [
        'zafiro' => 'Zafiro',
        'mineral' => 'Mineral',
        'hesalita' => 'Hesalita (acrílico)',
        'hibrido' => 'Híbrido',
    ],

    'strap_types' => [
        'acero' => 'Acero',
        'cuero' => 'Cuero',
        'caucho' => 'Caucho',
        'silicona' => 'Silicona',
        'nylon' => 'Nylon (NATO/Zulu)',
        'textil' => 'Textil',
        'ceramica' => 'Cerámica',
        'malla_milanesa' => 'Malla milanesa',
        'piel_sintetica' => 'Piel sintética',
        'integrado' => 'Integrado',
    ],

    'water_resistance_options' => [
        3 => '3 ATM · 30 m',
        5 => '5 ATM · 50 m',
        10 => '10 ATM · 100 m',
        20 => '20 ATM · 200 m',
        30 => '30 ATM · 300 m',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sugerencias para campos libres
    |--------------------------------------------------------------------------
    |
    | No son listas cerradas: material y color de caja son texto libre. Se
    | ofrecen como datalist para reducir variantes sin impedir valores nuevos.
    |
    */

    'case_material_suggestions' => [
        'Acero inoxidable',
        'Titanio',
        'Oro',
        'Oro rosa',
        'Plata',
        'Latón',
        'Carbono',
        'Cerámica',
        'Resina',
        'Aluminio',
    ],

    'case_color_suggestions' => [
        'Plateado',
        'Negro',
        'Dorado',
        'Oro rosa',
        'Azul',
        'Verde',
        'Bronce',
        'Blanco',
        'Gris',
    ],

    /*
    |--------------------------------------------------------------------------
    | Especificaciones informativas opcionales
    |--------------------------------------------------------------------------
    |
    | Se guardan en `products.specs` (JSONB) y se activan por producto. Cada
    | grupo se renderiza en el formulario administrativo y en la ficha. Un
    | campo solo se persiste y se muestra si el administrador lo activa y
    | tiene un valor.
    |
    | Tipos admitidos: text, number, textarea, select.
    |
    */

    'informative_groups' => [
        'movimiento' => [
            'label' => 'Movimiento',
            'fields' => [
                'calibre' => [
                    'label' => 'Calibre',
                    'type' => 'text',
                    'placeholder' => 'Ej. NH35, 4R36',
                ],
                'joyas' => [
                    'label' => 'Rubíes',
                    'type' => 'number',
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ],

        'caja' => [
            'label' => 'Caja',
            'fields' => [
                'acabado_caja' => [
                    'label' => 'Acabado',
                    'type' => 'text',
                    'placeholder' => 'Pulido, cepillado, mixto',
                ],
                'bisel' => [
                    'label' => 'Bisel',
                    'type' => 'text',
                ],
                'corona' => [
                    'label' => 'Corona',
                    'type' => 'text',
                ],
                'fondo' => [
                    'label' => 'Fondo',
                    'type' => 'text',
                ],
                'lug_to_lug_mm' => [
                    'label' => 'Distancia entre puntas de asas (mm)',
                    'type' => 'number',
                    'step' => '0.1',
                    'min' => 0,
                    'max' => 100,
                ],
                'distancia_asas_mm' => [
                    'label' => 'Distancia entre asas (mm)',
                    'type' => 'number',
                    'step' => '0.1',
                    'min' => 0,
                    'max' => 100,
                ],
                'peso_g' => [
                    'label' => 'Peso (g)',
                    'type' => 'number',
                    'min' => 0,
                    'max' => 1000,
                ],
                'ancho_correa_mm' => [
                    'label' => 'Ancho de correa (mm)',
                    'type' => 'number',
                    'step' => '0.1',
                    'min' => 0,
                    'max' => 100,
                ],
                'largo_correa_mm' => [
                    'label' => 'Largo de correa (mm)',
                    'type' => 'number',
                    'step' => '0.1',
                    'min' => 0,
                    'max' => 400,
                ],
            ],
        ],

        'esfera' => [
            'label' => 'Esfera',
            'fields' => [
                'color_esfera' => [
                    'label' => 'Color',
                    'type' => 'text',
                ],
                'acabado_esfera' => [
                    'label' => 'Acabado',
                    'type' => 'text',
                ],
                'indices' => [
                    'label' => 'Índices',
                    'type' => 'text',
                ],
                'agujas' => [
                    'label' => 'Agujas',
                    'type' => 'text',
                ],
                'lumen' => [
                    'label' => 'Lumen',
                    'type' => 'text',
                ],
                'ventana_fecha' => [
                    'label' => 'Ventana de fecha',
                    'type' => 'text',
                ],
                'segundero' => [
                    'label' => 'Segundero',
                    'type' => 'text',
                ],
            ],
        ],

        'cristal' => [
            'label' => 'Cristal',
            'fields' => [
                'forma_cristal' => [
                    'label' => 'Forma',
                    'type' => 'text',
                ],
                'antirreflejo' => [
                    'label' => 'Antirreflejo',
                    'type' => 'text',
                ],
            ],
        ],

        'correa' => [
            'label' => 'Correa',
            'fields' => [
                'material_correa' => [
                    'label' => 'Material',
                    'type' => 'text',
                ],
                'color_correa' => [
                    'label' => 'Color',
                    'type' => 'text',
                ],
                'cierre' => [
                    'label' => 'Cierre',
                    'type' => 'text',
                ],
                'extras_correa' => [
                    'label' => 'Extras',
                    'type' => 'text',
                ],
            ],
        ],

        'resistencia' => [
            'label' => 'Resistencia y entorno',
            'fields' => [
                'valvula_helio' => [
                    'label' => 'Válvula de helio',
                    'type' => 'select',
                    'options' => [
                        'si' => 'Sí',
                        'no' => 'No',
                    ],
                ],
                'iso_6425' => [
                    'label' => 'Certificación ISO 6425 (diver)',
                    'type' => 'select',
                    'options' => [
                        'si' => 'Sí',
                        'no' => 'No',
                    ],
                ],
                'resistencia_golpes' => [
                    'label' => 'Resistencia a golpes',
                    'type' => 'text',
                    'placeholder' => 'Ej. 5.000 G',
                ],
                'rango_temperatura' => [
                    'label' => 'Rango de temperatura',
                    'type' => 'text',
                    'placeholder' => 'Ej. -10 °C a 60 °C',
                ],
            ],
        ],

        'extras' => [
            'label' => 'Extras',
            'fields' => [
                'funciones' => [
                    'label' => 'Funciones / complicaciones',
                    'type' => 'textarea',
                    'rows' => 3,
                    'placeholder' => 'Ej. Cronógrafo, fecha, GMT',
                ],
                'pais_origen' => [
                    'label' => 'País de origen',
                    'type' => 'text',
                ],
                'edicion_limitada' => [
                    'label' => 'Edición limitada',
                    'type' => 'select',
                    'options' => [
                        'si' => 'Sí',
                        'no' => 'No',
                    ],
                ],
                'numero_edicion' => [
                    'label' => 'Número de edición',
                    'type' => 'text',
                ],
                'unidades_edicion' => [
                    'label' => 'Unidades de la edición',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 100000,
                ],
            ],
        ],
    ],

];
