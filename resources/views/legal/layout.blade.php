@extends('layouts.storefront')

@section('title', $title.' — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-8 sm:py-14')

@push('head')
    {{-- Impresión limpia: el documento no arrastra navbar, footer, avisos ni
         botones. El navegador ofrece guardar como PDF desde el mismo diálogo. --}}
    <style>
        @media print {
            [data-storefront-nav],
            [data-storefront-footer],
            [data-legal-actions],
            [role="note"] {
                display: none !important;
            }

            [data-legal-page] {
                max-width: none !important;
            }

            [data-legal-body] {
                color: #000 !important;
            }

            [data-legal-body] a {
                color: #000 !important;
                text-decoration: underline;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $legalLinks = [
            ['route' => 'legal.privacy', 'label' => 'Política de privacidad'],
            ['route' => 'legal.terms', 'label' => 'Términos y condiciones'],
            ['route' => 'legal.cookies', 'label' => 'Política de cookies'],
            ['route' => 'legal.returns', 'label' => 'Cambios y devoluciones'],
            ['route' => 'legal.shipping', 'label' => 'Envíos y entregas'],
            ['route' => 'legal.payments', 'label' => 'Medios de pago'],
            ['route' => 'legal.contact', 'label' => 'Contacto'],
        ];
        $isDraft = \App\Support\Legal::isDraft();
        $sicUrl = (string) config('legal.sic.url', 'https://www.sic.gov.co');
    @endphp

    <article data-legal-page="{{ $key }}" class="mx-auto w-full max-w-3xl">
        <header class="border-b border-border-soft pb-6">
            <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary">Legal</p>
            <h1 data-legal-title class="mt-2 font-display text-[32px] font-bold leading-[38px] tracking-[-0.01em] text-ink sm:text-[40px] sm:leading-[46px]">
                {{ $title }}
            </h1>
            <p data-legal-updated class="mt-3 font-body text-sm leading-6 text-ink-soft">
                Última actualización: {{ \App\Support\Legal::updatedAt($document) }} · Versión {{ \App\Support\Legal::version($document) }}
            </p>

            @if ($isDraft)
                <div data-legal-draft role="note" class="mt-5 border-l-2 border-accent bg-surface-subtle px-4 py-3 font-body text-sm leading-6 text-ink-soft">
                    <strong class="font-semibold text-ink">Borrador.</strong>
                    Este texto está pendiente de revisión por un abogado y de completar los datos del vendedor.
                    No debe publicarse tal como está.
                </div>
            @endif
        </header>

        <div
            data-legal-body
            class="mt-8 space-y-6 font-body text-[15px] leading-7 text-ink-soft
                [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4
                [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-[20px] [&_h2]:font-semibold [&_h2]:leading-7 [&_h2]:text-ink
                [&_h3]:mt-6 [&_h3]:font-display [&_h3]:text-[16px] [&_h3]:font-semibold [&_h3]:text-ink
                [&_li]:pl-1 [&_ol]:list-decimal [&_ol]:space-y-2 [&_ol]:pl-5
                [&_strong]:font-semibold [&_strong]:text-ink
                [&_table]:mt-4 [&_table]:w-full [&_table]:border-collapse [&_table]:text-sm
                [&_td]:border [&_td]:border-border-soft [&_td]:p-2 [&_td]:align-top
                [&_th]:border [&_th]:border-border-soft [&_th]:bg-surface-subtle [&_th]:p-2 [&_th]:text-left [&_th]:font-semibold [&_th]:text-ink
                [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5"
        >
            @yield('legal-content')
        </div>

        <footer data-legal-actions class="mt-12 border-t border-border-soft pt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    data-legal-print
                    onclick="window.print()"
                    class="inline-flex h-11 items-center justify-center rounded border border-primary px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-primary transition-colors hover:bg-primary hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    Imprimir o guardar en PDF
                </button>

                <a
                    data-legal-sic
                    href="{{ $sicUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="font-body text-sm text-ink-soft underline underline-offset-4 hover:text-primary"
                >
                    Superintendencia de Industria y Comercio (SIC)
                </a>
            </div>

            <nav data-legal-nav aria-label="Otros documentos legales" class="mt-8 border-t border-border-soft pt-6">
                <p class="font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink-muted">Documentos legales</p>
                <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-2 font-body text-sm">
                    @foreach ($legalLinks as $link)
                        <li>
                            <a
                                href="{{ route($link['route']) }}"
                                @if (request()->routeIs($link['route'])) aria-current="page" class="font-semibold text-ink" @else class="text-primary underline underline-offset-4" @endif
                            >
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </footer>
    </article>
@endsection
