<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Catálogo de demostración para desarrollo local.
 *
 * Usa las fotos reales que ya existen en la cuenta de Cloudinary del proyecto
 * y completa todas las columnas y especificaciones de la ficha técnica.
 *
 * No se ejecuta desde DatabaseSeeder ni desde ProductionSeeder: es un seeder
 * explícito para tener un catálogo visible en local.
 *
 *   php artisan db:seed --class=DemoCatalogSeeder
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->categories();
        $brands = $this->brands();

        $products = $this->products();

        foreach ($products as $data) {
            $categorySlug = $data['category'];
            $brandSlug = $data['brand'];

            unset($data['category'], $data['brand']);

            $data['category_id'] = $categories[$categorySlug]->id;
            $data['brand_id'] = $brands[$brandSlug]->id;

            Product::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }

        $this->collections($products);
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $categories = [];

        foreach (config('tagq.categories', []) as $category) {
            $categories[$category['slug']] = Category::firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, Brand>
     */
    private function brands(): array
    {
        $brands = [];

        foreach ([
            'loto' => 'Loto',
            'kairos' => 'Kairos',
            'rolex' => 'Rolex',
        ] as $slug => $name) {
            $brands[$slug] = Brand::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $brands;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function collections(array $products): void
    {
        $slugs = collect($products)->pluck('slug');

        $nuevos = Collection::firstOrCreate(
            ['slug' => 'nuevos-ingresos'],
            [
                'name' => 'Nuevos ingresos',
                'description' => 'Los últimos relojes que llegaron a la tienda.',
                'is_active' => true,
            ],
        );

        $bestSellers = Collection::firstOrCreate(
            ['slug' => 'best-sellers'],
            [
                'name' => 'Best sellers',
                'description' => 'Los modelos favoritos de nuestros clientes.',
                'is_active' => true,
            ],
        );

        $buceo = Collection::firstOrCreate(
            ['slug' => 'buceo'],
            [
                'name' => 'Buceo',
                'description' => 'Relojes preparados para el agua y la aventura.',
                'is_active' => true,
            ],
        );

        $nuevos->products()->sync(
            Product::whereIn('slug', $slugs)->pluck('id')->all()
        );

        $bestSellers->products()->sync(
            Product::whereIn('slug', [
                'loto-sport-cronografo-rojo',
                'loto-sport-digital-negro',
                'loto-classic-gold',
                'rolex-submariner-41-gold',
            ])->pluck('id')->all()
        );

        $buceo->products()->sync(
            Product::whereIn('slug', [
                'rolex-submariner-41-gold',
            ])->pluck('id')->all()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'category' => 'caballero',
                'brand' => 'loto',
                'subcategoria' => 'cronografo',
                'name' => 'Loto Sport Cronógrafo Rojo',
                'reference' => 'LOT-RED-01',
                'slug' => 'loto-sport-cronografo-rojo',
                'descripcion' => 'Reloj deportivo analógico-digital con caja reforzada en negro y rojo. Incluye cronógrafo, alarma, luz LED, doble hora y temporizador. Pensado para el uso diario y el entrenamiento.',
                'precio' => 189900,
                'stock' => 12,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1792626492/tag-q/producto/cnbayntvcpzjknfpbx0y.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780409908/tag-q/producto/pendientes/rgr1iw21m5dz7917v0fm.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236114/tag-q/producto/demo/red-dial.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Resina reforzada con acero inoxidable',
                'case_color' => 'Negro y rojo',
                'diameter_mm' => 48.0,
                'thickness_mm' => 15.5,
                'water_resistance_atm' => 10,
                'strap_type' => 'silicona',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Loto LD-6810',
                    'joyas' => 0,
                    'acabado_caja' => 'Mixto: pulido y mate',
                    'bisel' => 'Fijo con escala taquimétrica',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 54.0,
                    'distancia_asas_mm' => 24.0,
                    'peso_g' => 92,
                    'ancho_correa_mm' => 24.0,
                    'largo_correa_mm' => 260.0,
                    'color_esfera' => 'Negro',
                    'acabado_esfera' => 'Mate',
                    'indices' => 'Arábicos y barras aplicadas',
                    'agujas' => 'Luminiscentes con segundero central',
                    'lumen' => 'Sí, en agujas e índices',
                    'ventana_fecha' => 'Sí, digital a las 3',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Silicona',
                    'color_correa' => 'Negro',
                    'cierre' => 'Hebilla de acero',
                    'extras_correa' => 'Correa intercambiable',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Resistente a impactos',
                    'rango_temperatura' => '-10 °C a 50 °C',
                    'funciones' => 'Cronógrafo, alarma, luz LED, calendario, doble hora, temporizador',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'caballero',
                'brand' => 'loto',
                'subcategoria' => 'deportivo',
                'name' => 'Loto Sport Digital Negro',
                'reference' => 'LOT-BLK-02',
                'slug' => 'loto-sport-digital-negro',
                'descripcion' => 'Reloj deportivo con caja de resina reforzada, pantalla digital de doble lectura y sensor dual. Resistente al agua y a los impactos, con luz LED para uso nocturno.',
                'precio' => 169900,
                'stock' => 18,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780611091/tag-q/producto/hc3sq7cxcql09jdavuqj.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236116/tag-q/producto/demo/black-dial.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236124/tag-q/producto/demo/black-case.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Resina reforzada',
                'case_color' => 'Negro con detalles dorados',
                'diameter_mm' => 50.0,
                'thickness_mm' => 16.0,
                'water_resistance_atm' => 10,
                'strap_type' => 'silicona',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Loto LD-9000',
                    'joyas' => 0,
                    'acabado_caja' => 'Mate con tornillos dorados',
                    'bisel' => 'Fijo con escala de brújula',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 56.0,
                    'distancia_asas_mm' => 26.0,
                    'peso_g' => 98,
                    'ancho_correa_mm' => 26.0,
                    'largo_correa_mm' => 265.0,
                    'color_esfera' => 'Negro',
                    'acabado_esfera' => 'Mate',
                    'indices' => 'Arábicos y barras',
                    'agujas' => 'Luminiscentes',
                    'lumen' => 'Sí, en agujas e índices',
                    'ventana_fecha' => 'Sí, digital a las 3',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Silicona',
                    'color_correa' => 'Negro',
                    'cierre' => 'Hebilla de acero',
                    'extras_correa' => 'Correa intercambiable',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Resistente a impactos',
                    'rango_temperatura' => '-10 °C a 50 °C',
                    'funciones' => 'Hora analógica y digital, cronógrafo, alarma, luz LED, calendario, temporizador, brújula y altímetro',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'ninos',
                'brand' => 'loto',
                'subcategoria' => 'deportivo',
                'name' => 'Loto Kids Rosa',
                'reference' => 'LOT-KID-03',
                'slug' => 'loto-kids-rosa',
                'descripcion' => 'Reloj infantil con diseño rosa, caja resistente y correa de silicona suave. Combina hora analógica y digital, alarma, cronógrafo y luz LED.',
                'precio' => 99900,
                'stock' => 15,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780612026/tag-q/producto/kwuaikkktlp77gj483no.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236131/tag-q/producto/demo/pink-dial.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236136/tag-q/producto/demo/pink-case.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Resina',
                'case_color' => 'Rosa',
                'diameter_mm' => 40.0,
                'thickness_mm' => 13.0,
                'water_resistance_atm' => 5,
                'strap_type' => 'silicona',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Loto LD-1200',
                    'joyas' => 0,
                    'acabado_caja' => 'Mate',
                    'bisel' => 'Fijo rosa',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 45.0,
                    'distancia_asas_mm' => 20.0,
                    'peso_g' => 55,
                    'ancho_correa_mm' => 20.0,
                    'largo_correa_mm' => 230.0,
                    'color_esfera' => 'Rosa',
                    'acabado_esfera' => 'Mate',
                    'indices' => 'Arábicos y barras',
                    'agujas' => 'Rosa y blanca',
                    'lumen' => 'No',
                    'ventana_fecha' => 'No',
                    'segundero' => 'Sí, digital',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Silicona',
                    'color_correa' => 'Rosa',
                    'cierre' => 'Hebilla de plástico reforzado',
                    'extras_correa' => 'Correa suave hipoalergénica',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Resistente a golpes leves',
                    'rango_temperatura' => '0 °C a 40 °C',
                    'funciones' => 'Hora analógica y digital, alarma, cronógrafo, luz LED, calendario',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'caballero',
                'brand' => 'loto',
                'subcategoria' => 'formal',
                'name' => 'Loto Classic Gold',
                'reference' => 'LOT-CLS-04',
                'slug' => 'loto-classic-gold',
                'descripcion' => 'Reloj clásico de vestir con caja dorada, esfera blanca y correa de cuero negro. Un diseño sobrio para oficina, eventos y uso diario.',
                'precio' => 259900,
                'stock' => 8,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1792626489/tag-q/producto/d27pvbdz2hufu01klrig.png',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780411257/tag-q/producto/ofx0jlrxhnnydzi13yg9.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780411262/tag-q/producto/x4lkc1iudagiof3nflzg.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Acero inoxidable con baño de oro',
                'case_color' => 'Dorado',
                'diameter_mm' => 40.0,
                'thickness_mm' => 8.5,
                'water_resistance_atm' => 3,
                'strap_type' => 'cuero',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Loto 2035',
                    'joyas' => 0,
                    'acabado_caja' => 'Pulido',
                    'bisel' => 'Liso dorado',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 45.0,
                    'distancia_asas_mm' => 20.0,
                    'peso_g' => 58,
                    'ancho_correa_mm' => 20.0,
                    'largo_correa_mm' => 240.0,
                    'color_esfera' => 'Blanco',
                    'acabado_esfera' => 'Liso',
                    'indices' => 'Arábicos dorados',
                    'agujas' => 'Doradas',
                    'lumen' => 'No',
                    'ventana_fecha' => 'No',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Cuero',
                    'color_correa' => 'Negro',
                    'cierre' => 'Hebilla dorada',
                    'extras_correa' => 'Correa de repuesto',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Uso diario',
                    'rango_temperatura' => '0 °C a 40 °C',
                    'funciones' => 'Hora, minuto y segundero',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'unisex',
                'brand' => 'kairos',
                'subcategoria' => 'casual',
                'name' => 'Kairos Classic Two-Tone',
                'reference' => 'KAI-TT-01',
                'slug' => 'kairos-classic-two-tone',
                'descripcion' => 'Reloj de acero inoxidable bicolor con esfera blanca texturizada y brazalete ajustable. Un equilibrio entre elegancia clásica y uso cotidiano.',
                'precio' => 289900,
                'stock' => 6,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1792626490/tag-q/producto/tzvqlqmpyxi4idqc5fow.png',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236140/tag-q/producto/demo/ttsmooth-dial.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Acero inoxidable bicolor',
                'case_color' => 'Plateado y dorado',
                'diameter_mm' => 40.0,
                'thickness_mm' => 9.0,
                'water_resistance_atm' => 5,
                'strap_type' => 'acero',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Kairos 2035',
                    'joyas' => 0,
                    'acabado_caja' => 'Pulido',
                    'bisel' => 'Liso dorado',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 44.0,
                    'distancia_asas_mm' => 20.0,
                    'peso_g' => 105,
                    'ancho_correa_mm' => 20.0,
                    'largo_correa_mm' => 220.0,
                    'color_esfera' => 'Blanco con textura',
                    'acabado_esfera' => 'Gofrado',
                    'indices' => 'Barras doradas',
                    'agujas' => 'Doradas',
                    'lumen' => 'No',
                    'ventana_fecha' => 'No',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Acero inoxidable',
                    'color_correa' => 'Bicolor',
                    'cierre' => 'Mariposa',
                    'extras_correa' => 'Eslabones ajustables',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Uso diario',
                    'rango_temperatura' => '0 °C a 40 °C',
                    'funciones' => 'Hora, minuto y segundero',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'dama',
                'brand' => 'kairos',
                'subcategoria' => 'formal',
                'name' => 'Kairos Elegance Two-Tone',
                'reference' => 'KAI-TT-02',
                'slug' => 'kairos-elegance-two-tone',
                'descripcion' => 'Reloj femenino bicolor con bisel dorado acanalado, esfera blanca gofrada y brazalete de acero. Diseñado para ocasiones formales y uso diario.',
                'precio' => 299900,
                'stock' => 7,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780413213/tag-q/producto/h30hvpt0xcei1nogt7o3.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236145/tag-q/producto/demo/ttfluted-dial.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Acero inoxidable bicolor',
                'case_color' => 'Plateado con bisel dorado',
                'diameter_mm' => 38.0,
                'thickness_mm' => 8.5,
                'water_resistance_atm' => 3,
                'strap_type' => 'acero',
                'movimiento' => 'cuarzo',
                'cristal' => 'mineral',
                'specs' => [
                    'calibre' => 'Kairos 2035',
                    'joyas' => 0,
                    'acabado_caja' => 'Pulido y cepillado',
                    'bisel' => 'Acanalado dorado',
                    'corona' => 'A presión',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 42.0,
                    'distancia_asas_mm' => 18.0,
                    'peso_g' => 92,
                    'ancho_correa_mm' => 18.0,
                    'largo_correa_mm' => 210.0,
                    'color_esfera' => 'Blanco',
                    'acabado_esfera' => 'Gofrado',
                    'indices' => 'Barras doradas',
                    'agujas' => 'Doradas',
                    'lumen' => 'No',
                    'ventana_fecha' => 'No',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'No',
                    'material_correa' => 'Acero inoxidable',
                    'color_correa' => 'Bicolor',
                    'cierre' => 'Mariposa',
                    'extras_correa' => 'Eslabones ajustables',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Uso diario',
                    'rango_temperatura' => '0 °C a 40 °C',
                    'funciones' => 'Hora, minuto y segundero',
                    'pais_origen' => 'China',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'dama',
                'brand' => 'rolex',
                'subcategoria' => 'formal',
                'name' => 'Rolex Datejust 36 Floral',
                'reference' => 'RX-DJ-36',
                'slug' => 'rolex-datejust-36-floral',
                'descripcion' => 'Reloj automático de lujo con esfera floral, caja bicolor de acero y oro rosa, cristal de zafiro y brazalete Oyster. Una pieza de colección para ocasiones especiales.',
                'precio' => 1290000,
                'stock' => 3,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1792626488/tag-q/producto/deoldpqv599ytkxmh9bu.png',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236149/tag-q/producto/demo/crown-dial.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Acero inoxidable y oro rosa',
                'case_color' => 'Bicolor',
                'diameter_mm' => 36.0,
                'thickness_mm' => 11.0,
                'water_resistance_atm' => 10,
                'strap_type' => 'acero',
                'movimiento' => 'automatico',
                'cristal' => 'zafiro',
                'specs' => [
                    'calibre' => 'Rolex 3235',
                    'joyas' => 31,
                    'acabado_caja' => 'Pulido y cepillado',
                    'bisel' => 'Liso de oro rosa',
                    'corona' => 'A rosca',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 44.0,
                    'distancia_asas_mm' => 20.0,
                    'peso_g' => 120,
                    'ancho_correa_mm' => 20.0,
                    'largo_correa_mm' => 190.0,
                    'color_esfera' => 'Rosa con motivo floral',
                    'acabado_esfera' => 'Gofrado',
                    'indices' => 'Barras con detalles de diamantes',
                    'agujas' => 'Oro rosa',
                    'lumen' => 'Sí, en agujas e índices',
                    'ventana_fecha' => 'Sí, a las 3 con lupa',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'Sí',
                    'material_correa' => 'Acero inoxidable y oro rosa',
                    'color_correa' => 'Bicolor',
                    'cierre' => 'Oysterlock',
                    'extras_correa' => 'Eslabones ajustables',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'no',
                    'resistencia_golpes' => 'Uso diario',
                    'rango_temperatura' => '-10 °C a 60 °C',
                    'funciones' => 'Hora, minuto, segundero y fecha',
                    'pais_origen' => 'Suiza',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
            [
                'category' => 'caballero',
                'brand' => 'rolex',
                'subcategoria' => 'buceo',
                'name' => 'Rolex Submariner 41 Gold',
                'reference' => 'RX-SUB-41',
                'slug' => 'rolex-submariner-41-gold',
                'descripcion' => 'Reloj de buceo automático con caja y brazalete de oro amarillo, bisel cerámico negro y 300 metros de resistencia al agua. Diseñado para el agua y la aventura.',
                'precio' => 1590000,
                'stock' => 4,
                'imagenes' => [
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1780611034/tag-q/producto/jv9ftbjll0qdhk6pldmv.jpg',
                    'https://res.cloudinary.com/dg6iut6sl/image/upload/v1793236154/tag-q/producto/demo/aqua-dial.jpg',
                ],
                'case_shape' => 'redonda',
                'case_material' => 'Oro amarillo 18k',
                'case_color' => 'Dorado',
                'diameter_mm' => 41.0,
                'thickness_mm' => 12.0,
                'water_resistance_atm' => 30,
                'strap_type' => 'acero',
                'movimiento' => 'automatico',
                'cristal' => 'zafiro',
                'specs' => [
                    'calibre' => 'Rolex 3235',
                    'joyas' => 31,
                    'acabado_caja' => 'Pulido y cepillado',
                    'bisel' => 'Cerámico negro giratorio unidireccional',
                    'corona' => 'A rosca con protección',
                    'fondo' => 'Acero atornillado',
                    'lug_to_lug_mm' => 47.0,
                    'distancia_asas_mm' => 21.0,
                    'peso_g' => 160,
                    'ancho_correa_mm' => 21.0,
                    'largo_correa_mm' => 190.0,
                    'color_esfera' => 'Negro',
                    'acabado_esfera' => 'Lacado',
                    'indices' => 'Barras con luminova',
                    'agujas' => 'Mercedes doradas',
                    'lumen' => 'Sí, en agujas e índices',
                    'ventana_fecha' => 'Sí, a las 3 con lupa',
                    'segundero' => 'Sí, central',
                    'forma_cristal' => 'Plana',
                    'antirreflejo' => 'Sí',
                    'material_correa' => 'Oro amarillo 18k',
                    'color_correa' => 'Dorado',
                    'cierre' => 'Oysterlock con Glidelock',
                    'extras_correa' => 'Extensiones de buceo',
                    'valvula_helio' => 'no',
                    'iso_6425' => 'si',
                    'resistencia_golpes' => 'Uso profesional',
                    'rango_temperatura' => '-10 °C a 60 °C',
                    'funciones' => 'Hora, minuto, segundero, fecha y bisel giratorio',
                    'pais_origen' => 'Suiza',
                    'edicion_limitada' => 'no',
                ],
                'is_active' => true,
            ],
        ];
    }
}
