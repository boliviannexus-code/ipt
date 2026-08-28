<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17233c">
    <title>Iniciar sesión | {{ config('app.name', 'Base Admin') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-story" aria-labelledby="auth-story-title">
        <div class="auth-story-brand">
            <span class="auth-brand-mark" aria-hidden="true"><i class="ti ti-building-store"></i></span>
            <span>{{ config('app.name', 'Base Admin') }}</span>
        </div>

        <div class="auth-story-content">
            <span class="auth-eyebrow">Acceso administrativo</span>
            <h1 id="auth-story-title">Tu espacio de trabajo, protegido desde el primer paso.</h1>
            <p>Ingresa con tu cuenta asignada. El sistema verificará tu identidad y los accesos correspondientes a tu rol.</p>

            <ol class="auth-security-path" aria-label="Proceso de acceso seguro">
                <li>
                    <span class="auth-security-icon" aria-hidden="true"><i class="ti ti-id"></i></span>
                    <span><strong>Identidad verificada</strong><small>Validamos tus credenciales de acceso.</small></span>
                </li>
                <li>
                    <span class="auth-security-icon" aria-hidden="true"><i class="ti ti-lock"></i></span>
                    <span><strong>Sesión protegida</strong><small>Creamos una sesión privada para este dispositivo.</small></span>
                </li>
                <li>
                    <span class="auth-security-icon" aria-hidden="true"><i class="ti ti-shield-check"></i></span>
                    <span><strong>Acceso controlado</strong><small>Mostramos únicamente las funciones autorizadas.</small></span>
                </li>
            </ol>
        </div>

        <p class="auth-story-footnote"><i class="ti ti-lock-check me-1" aria-hidden="true"></i>Conexión y sesión protegidas</p>
    </section>

    <section class="auth-form-side" aria-labelledby="login-title">
        <div class="auth-mobile-brand">
            <span class="auth-brand-mark" aria-hidden="true"><i class="ti ti-building-store"></i></span>
            <span>{{ config('app.name', 'Base Admin') }}</span>
        </div>

        <div class="auth-form-wrap">
            <header class="auth-form-header">
                <span class="auth-eyebrow">Bienvenido</span>
                <h2 id="login-title">Inicia sesión</h2>
                <p>Usa tu correo electrónico y la contraseña de tu cuenta.</p>
            </header>

            <form method="POST" action="{{ route('login.store') }}" data-login-form novalidate>
                @csrf

                <div class="auth-field">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <div class="auth-input">
                        <i class="ti ti-mail" aria-hidden="true"></i>
                        <input
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            placeholder="nombre@empresa.com"
                            aria-describedby="email-error"
                            required
                            autofocus
                        >
                    </div>
                    <div class="invalid-feedback {{ $errors->has('email') ? 'd-block' : '' }}" id="email-error" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                </div>

                <div class="auth-field">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="auth-input auth-password-input">
                        <i class="ti ti-key" aria-hidden="true"></i>
                        <input
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            aria-describedby="password-error"
                            required
                        >
                        <button
                            class="auth-password-toggle"
                            type="button"
                            data-password-toggle
                            aria-controls="password"
                            aria-label="Mostrar contraseña"
                        >
                            <i class="ti ti-eye" data-password-toggle-icon aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback {{ $errors->has('password') ? 'd-block' : '' }}" id="password-error" role="alert">
                        {{ $errors->first('password') }}
                    </div>
                </div>

                <div class="auth-form-options">
                    <label class="form-check">
                        <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span class="form-check-label">Mantener mi sesión</span>
                    </label>
                    <span class="auth-option-help" title="No uses esta opción en equipos compartidos">
                        Solo en equipos privados
                    </span>
                </div>

                <button class="btn btn-primary auth-submit" type="submit" data-login-submit>
                    <span class="spinner-border spinner-border-sm d-none" data-login-spinner aria-hidden="true"></span>
                    <span data-login-submit-label>Ingresar al sistema</span>
                    <i class="ti ti-arrow-right" data-login-arrow aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-support-note">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                Si no puedes ingresar, solicita a un administrador que revise el estado de tu cuenta.
            </p>
        </div>

        <footer class="auth-footer">
            <span>{{ config('app.name', 'Base Admin') }}</span>
            <span aria-hidden="true">·</span>
            <span>Acceso seguro</span>
        </footer>
    </section>
</main>
</body>
</html>
