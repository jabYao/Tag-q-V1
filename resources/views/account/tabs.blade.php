@php($current = $current ?? 'data')

<nav aria-label="Secciones de mi cuenta" class="mt-8 flex flex-wrap gap-x-5 gap-y-2 border-b border-gray-200 text-sm">
    <a
        href="{{ route('customer.account.edit') }}"
        @class([
            '-mb-px border-b-2 pb-2 font-medium transition-colors',
            'border-[#00052c] text-[#00052c]' => $current === 'data',
            'border-transparent text-gray-500 hover:text-gray-900' => $current !== 'data',
        ])
        @if ($current === 'data') aria-current="page" @endif
    >Mis datos</a>

    <a
        href="{{ route('customer.account.password.edit') }}"
        @class([
            '-mb-px border-b-2 pb-2 font-medium transition-colors',
            'border-[#00052c] text-[#00052c]' => $current === 'password',
            'border-transparent text-gray-500 hover:text-gray-900' => $current !== 'password',
        ])
        @if ($current === 'password') aria-current="page" @endif
    >Contraseña</a>

    <a
        href="{{ route('customer.orders.index') }}"
        class="-mb-px border-b-2 border-transparent pb-2 font-medium text-gray-500 transition-colors hover:text-gray-900"
    >Mis pedidos</a>
</nav>
