@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight">Tag q</h1>
            <p class="text-sm text-gray-500 mt-1">Crea tu cuenta</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    autofocus
                    class="w-full rounded border px-3 py-2 border-gray-300 focus:border-black focus:ring-black @error('name') border-red-500 @enderror"
                >
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    class="w-full rounded border px-3 py-2 border-gray-300 focus:border-black focus:ring-black @error('email') border-red-500 @enderror"
                >
                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    class="w-full rounded border px-3 py-2 border-gray-300 focus:border-black focus:ring-black @error('password') border-red-500 @enderror"
                >
                @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded border px-3 py-2 border-gray-300 focus:border-black focus:ring-black"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-black text-white py-2.5 rounded font-medium hover:bg-gray-800 transition"
            >
                Crear cuenta
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-gray-800 font-medium hover:underline">Inicia sesión</a>
        </p>
    </div>
@endsection
