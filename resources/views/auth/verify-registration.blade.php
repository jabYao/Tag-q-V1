@extends('layouts.guest')

@section('title', 'Verificar correo')

@section('content')
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight">Tag q</h1>
            <p class="text-sm text-gray-500 mt-1">Verifica tu correo</p>
        </div>

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded p-3 mb-4">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            <p class="text-sm text-gray-600">
                Enviamos un código de 6 dígitos a <span class="font-medium text-gray-800">{{ $email }}</span>.
                El código vence en 10 minutos.
            </p>

            <form method="POST" action="{{ route('register.verify.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Código de verificación</label>
                    <input
                        id="code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        class="w-full rounded border px-3 py-2 tracking-widest text-center text-lg border-gray-300 focus:border-black focus:ring-black @error('code') border-red-500 @enderror"
                    >
                    @error('code')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full bg-black text-white py-2.5 rounded font-medium hover:bg-gray-800 transition"
                >
                    Verificar
                </button>
            </form>

            <form method="POST" action="{{ route('register.resend') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    Reenviar código
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="{{ route('register') }}" class="hover:text-gray-600">← Volver al registro</a>
        </p>
    </div>
@endsection
