@extends('layouts.storefront')

@section('title', 'Verificar cambio de contraseña — '.config('app.name', 'Tag q'))

@section('main-class', 'site-container py-10 sm:py-14 lg:py-16')

@section('content')
    <div data-auth="account" class="auth-page">
        <div class="auth-shell">
            <section class="auth-panel" aria-labelledby="account-password-verify-title">
                <header>
                    <p class="auth-eyebrow">MI CUENTA</p>
                    <h1 id="account-password-verify-title" class="auth-title">Revisa tu correo</h1>
                    <p class="auth-subtitle">
                        Enviamos un código de 6 dígitos a <strong>{{ $user->email }}</strong>.
                        Ingrésalo para confirmar tu nueva contraseña.
                    </p>
                </header>

                @include('account.tabs', ['current' => 'password'])

                @if (session('status'))
                    <div class="auth-alert" role="status">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="8" r="6.5" />
                            <path d="m5 8 2 2 4-4.5" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if ($verification->isExpired())
                    <div class="auth-alert auth-alert--error" role="alert">
                        <p>El código venció. Solicita uno nuevo.</p>
                    </div>
                @elseif ($verification->hasExceededAttempts())
                    <div class="auth-alert auth-alert--error" role="alert">
                        <p>Superaste el número de intentos. Solicita un nuevo código.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.account.password.verify.submit') }}" class="auth-form">
                    @csrf

                    @php($codeDescribedBy = $errors->has('code')
                        ? 'password-code-hint password-code-error'
                        : 'password-code-hint')

                    <div class="auth-field">
                        <span id="password-code-label" class="auth-label">Código de verificación</span>
                        <div class="auth-otp" data-otp role="group" aria-labelledby="password-code-label">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    name="code[]"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="1"
                                    @if ($i === 0)
                                        autocomplete="one-time-code"
                                        autofocus
                                    @else
                                        autocomplete="off"
                                    @endif
                                    required
                                    class="auth-otp-input"
                                    data-otp-digit
                                    aria-label="Dígito {{ $i + 1 }} de 6"
                                    aria-describedby="{{ $codeDescribedBy }}"
                                    @error('code') aria-invalid="true" @enderror
                                >
                            @endfor
                        </div>
                        <p id="password-code-hint" class="auth-hint">
                            El código tiene 6 dígitos y vence en
                            {{ \App\Models\PasswordChangeVerification::CODE_TTL_MINUTES }} minutos.
                        </p>
                        @error('code')
                            <p id="password-code-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="auth-submit">Confirmar cambio</button>
                </form>

                <form method="POST" action="{{ route('customer.account.password.verify.resend') }}">
                    @csrf
                    <button type="submit" class="auth-resend">Reenviar código</button>
                </form>

                @error('send_code')
                    <p class="auth-error" role="alert">{{ $message }}</p>
                @enderror
            </section>
        </div>
    </div>

    <script>
        (() => {
            const initOtpInputs = () => {
                document.querySelectorAll('[data-otp]').forEach((group) => {
                    if (group.dataset.otpReady === 'true') {
                        return;
                    }

                    group.dataset.otpReady = 'true';

                    const inputs = Array.from(group.querySelectorAll('[data-otp-digit]'));

                    const focusAt = (index) => {
                        const input = inputs[Math.max(0, Math.min(index, inputs.length - 1))];

                        if (input) {
                            input.focus();
                            input.select();
                        }
                    };

                    const distribute = (start, value) => {
                        const digits = value.replace(/\D/g, '').slice(0, inputs.length - start);

                        digits.split('').forEach((digit, offset) => {
                            inputs[start + offset].value = digit;
                        });

                        focusAt(start + digits.length);
                    };

                    inputs.forEach((input, index) => {
                        input.addEventListener('input', () => {
                            const digits = input.value.replace(/\D/g, '');

                            // El autocompletado móvil puede meter el código
                            // completo en un solo cuadro: lo repartimos igual
                            // que al pegar.
                            if (digits.length > 1) {
                                input.value = '';
                                distribute(index, digits);
                                return;
                            }

                            input.value = digits.slice(0, 1);

                            if (input.value && index < inputs.length - 1) {
                                focusAt(index + 1);
                            }
                        });

                        input.addEventListener('keydown', (event) => {
                            if (event.key === 'Backspace' && input.value === '' && index > 0) {
                                event.preventDefault();
                                inputs[index - 1].value = '';
                                focusAt(index - 1);
                            } else if (event.key === 'ArrowLeft' && index > 0) {
                                event.preventDefault();
                                focusAt(index - 1);
                            } else if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                                event.preventDefault();
                                focusAt(index + 1);
                            }
                        });

                        input.addEventListener('paste', (event) => {
                            event.preventDefault();

                            const text = (event.clipboardData || window.clipboardData).getData('text') || '';
                            distribute(index, text);
                        });
                    });
                });
            };

            initOtpInputs();
            document.addEventListener('livewire:navigated', initOtpInputs);
        })();
    </script>
@endsection
