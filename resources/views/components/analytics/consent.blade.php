@props(['measurementId' => null])

@php
    $measurementId = $measurementId ?? config('services.google.analytics_id');
    $decision = request()->cookie('tagq_analytics_consent');

    // El aviso solo existe mientras no haya una decisión válida. Aceptar o
    // rechazar escribe `tagq_analytics_consent`, y desde ahí el servidor deja
    // de pintarlo: no se pregunta dos veces.
    $decided = in_array($decision, ['accepted', 'rejected'], true);
@endphp

@if ($measurementId && ! $decided)
    <section
        data-analytics-consent
        data-analytics-id="{{ $measurementId }}"
        aria-label="Consentimiento de cookies de analítica"
        class="fixed inset-x-0 bottom-0 z-[80] border-t border-border-soft bg-surface shadow-[0_-6px_24px_rgba(0,5,44,0.12)]"
    >
        <div class="site-container flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-8">
            <p class="max-w-2xl font-body text-[13px] leading-6 text-ink-soft">
                Usamos Google Analytics para entender cómo se usa la tienda y mejorarla. Solo se
                activa si lo aceptas; si lo rechazas puedes comprar con normalidad. Detalles en la
                <a
                    href="{{ route('legal.cookies') }}"
                    class="text-primary underline underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >política de cookies</a>.
            </p>

            {{-- Las dos acciones comparten tamaño y tipografía: rechazar no es
                 más difícil de encontrar que aceptar. --}}
            <div class="flex shrink-0 gap-3">
                <button
                    type="button"
                    data-analytics-reject
                    class="inline-flex h-11 flex-1 items-center justify-center rounded border border-border-soft bg-surface-muted px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-ink transition-colors hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:flex-none"
                >
                    Rechazar
                </button>
                <button
                    type="button"
                    data-analytics-accept
                    class="inline-flex h-11 flex-1 items-center justify-center rounded bg-primary px-5 font-display text-[11px] font-semibold uppercase tracking-[0.15em] text-white transition-colors hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:flex-none"
                >
                    Aceptar
                </button>
            </div>
        </div>
    </section>
@endif
