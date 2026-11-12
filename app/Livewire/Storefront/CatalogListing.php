<?php

namespace App\Livewire\Storefront;

use App\Support\CatalogQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catálogo público reactivo.
 *
 * El controlador valida el query string y entrega los filtros ya validados.
 * Este componente conserva esos filtros en una propiedad bloqueada, resuelve
 * la consulta con CatalogQuery y vuelve a renderizar la grilla cuando cambia
 * el orden, sin recargar la página.
 *
 * El orden vive en la URL (`?orden=`), omitiendo el valor por defecto, para
 * que un enlace compartido conserve la selección. El panel de filtros mantiene
 * su botón GET; `draft` guarda las ediciones aún no aplicadas para que cambiar
 * el orden no borre lo que el usuario escribió.
 *
 * La grilla arranca con un primer lote y el botón «Ver más» suma los
 * siguientes sin recargar; `perPage` guarda cuántos productos se están
 * mostrando. La paginación clásica solo se renderiza dentro de `<noscript>`.
 */
class CatalogListing extends Component
{
    use WithPagination;

    /** Opciones del desplegable «Ordenar por». */
    public const SORT_OPTIONS = [
        'recientes' => 'Más recientes',
        'precio_asc' => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
        'nombre_asc' => 'Nombre: A–Z',
    ];

    /**
     * Filtros validados por CatalogFilterRequest. Bloqueados para que el
     * cliente no pueda alterar la consulta desde el snapshot de Livewire.
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $filters = [];

    /** Orden vigente, sincronizado con `?orden=`. */
    #[Url(as: 'orden', except: 'recientes')]
    public string $sort = 'recientes';

    /**
     * Valores visibles del formulario de filtros. Pueden diferir de `filters`
     * mientras el usuario edita sin pulsar «Aplicar filtros».
     *
     * @var array<string, mixed>
     */
    public array $draft = [];

    /**
     * Cantidad de productos visibles. Empieza con el primer lote y crece de a
     * `CatalogQuery::PER_PAGE` cada vez que se pulsa «Ver más».
     */
    public int $perPage = CatalogQuery::PER_PAGE;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mount(array $filters = []): void
    {
        $this->filters = $filters;

        $this->sort = array_key_exists($filters['orden'] ?? '', self::SORT_OPTIONS)
            ? $filters['orden']
            : 'recientes';

        $this->draft = $this->initialDraft($filters);
    }

    /**
     * Un orden desconocido vuelve al valor por defecto y cualquier cambio
     * regresa a la primera página.
     */
    public function updatedSort(): void
    {
        if (! array_key_exists($this->sort, self::SORT_OPTIONS)) {
            $this->sort = 'recientes';
        }

        $this->resetPage();
    }

    /**
     * Suma otro lote de productos al final de la grilla sin recargar.
     *
     * Vuelve a la primera página porque el tamaño de página cambia: así el
     * botón funciona igual si se llegó desde un enlace antiguo con `?page=2`.
     */
    public function loadMore(): void
    {
        $this->perPage += CatalogQuery::PER_PAGE;
        $this->resetPage();
    }

    public function render(): View
    {
        $filters = $this->filters;
        $filters['orden'] = $this->sort;

        $query = new CatalogQuery($filters);

        return view('livewire.storefront.catalog-listing', [
            'products' => $query->paginate($this->perPage),
            'categories' => $query->categories(),
            'options' => $query->options(),
            'filters' => $filters,
            'draft' => $this->draft,
            'sortOptions' => self::SORT_OPTIONS,
            'currentSort' => $this->sort,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function initialDraft(array $filters): array
    {
        $draft = [];

        foreach ([
            'q',
            'categoria',
            'subcategoria',
            'marca',
            'strap_type',
            'water_resistance_atm',
            'material_pulso',
            'cristal',
            'movimiento',
            'diametro_min',
            'diametro_max',
            'grosor_min',
            'grosor_max',
            'precio_min',
            'precio_max',
        ] as $key) {
            $draft[$key] = $filters[$key] ?? '';
        }

        $draft['disponibilidad'] = $filters['disponibilidad'] ?? 'todos';

        return $draft;
    }
}
