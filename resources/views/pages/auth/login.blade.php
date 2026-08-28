@php
    $site = \App\Models\SiteSetting::current();
    $presentation = config('portfolio.presentation');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $presentation['default_theme'] }}">
    <head>
        <x-portfolio.head :title="__('portfolio.login.title')" />
        @vite('resources/js/passkeys.js')
    </head>

    <body data-hz-config="{{ json_encode(['background' => (bool) $presentation['background']]) }}">
        <canvas data-hz-bg class="hz-bg-field" aria-hidden="true"></canvas>

        <div class="hz-login">
            <div class="card elev-md hz-login-card">
                <div class="hz-login-brand">
                    <x-portfolio.globe />
                    <span>{{ $site->profile_initials }}</span>
                </div>

                <div>
                    <h2>{{ __('portfolio.login.title') }}</h2>
                    <p class="text-muted" style="margin: 0; font-size: 14px;">{{ __('portfolio.login.sub') }}</p>
                </div>

                @if (session('status'))
                    <div class="hz-login-alert">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="hz-login-alert">{{ $errors->first() }}</div>
                @endif

                <div
                    x-data="{
                        supported: false,
                        loading: false,
                        error: null,
                        init() {
                            this.check();
                            window.addEventListener('passkeys:ready', () => this.check(), { once: true });
                        },
                        check() {
                            this.supported = Boolean(window.Passkeys?.isSupported());
                        },
                        async verify() {
                            this.loading = true;
                            this.error = null;

                            try {
                                const response = await window.Passkeys.verify({
                                    routes: {
                                        options: '{{ route('passkey.login-options') }}',
                                        submit: '{{ route('passkey.login') }}',
                                    },
                                });

                                window.location.href = response.redirect || '{{ route('dashboard') }}';
                            } catch (e) {
                                if (e.constructor?.name !== 'UserCancelledError') {
                                    this.error = e.message;
                                }
                            } finally {
                                this.loading = false;
                            }
                        },
                    }"
                    x-cloak
                    x-show="supported"
                >
                    <button
                        type="button"
                        class="btn btn-secondary"
                        style="width: 100%;"
                        x-on:click="verify()"
                        x-bind:disabled="loading"
                    >
                        <span x-show="!loading">{{ __('Sign in with a passkey') }}</span>
                        <span x-show="loading" x-cloak>{{ __('Authenticating...') }}</span>
                    </button>

                    <p class="hz-login-alert" style="margin-top: 10px;" x-show="error" x-text="error" x-cloak></p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="hz-form">
                    @csrf

                    <div class="field">
                        <label for="login-email">{{ __('portfolio.login.email') }}</label>
                        <input
                            id="login-email"
                            class="input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>

                    <div class="field">
                        <label for="login-password">{{ __('portfolio.login.password') }}</label>
                        <input
                            id="login-password"
                            class="input"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <label class="check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        {{ __('portfolio.login.remember') }}
                    </label>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px 16px; font-size: 15px;">
                        {{ __('portfolio.login.submit') }}
                    </button>
                </form>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size: 13px;">
                        {{ __('portfolio.login.forgot') }}
                    </a>
                @endif

                <div class="text-muted hz-login-note">{{ __('portfolio.login.note') }}</div>

                <a href="{{ route('home') }}" class="btn btn-ghost" style="align-self: flex-start;">
                    {{ __('portfolio.actions.back_site') }}
                </a>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
