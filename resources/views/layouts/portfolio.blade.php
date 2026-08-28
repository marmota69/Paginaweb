@php
    $site = \App\Models\SiteSetting::current();
    $presentation = config('portfolio.presentation');
    $runtime = [
        'background' => (bool) $presentation['background'],
        'introDuration' => (float) $presentation['intro_duration'],
        'introAlways' => (bool) $presentation['intro_always'],
        'sceneDuration' => (int) $presentation['scene_duration'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $presentation['default_theme'] }}">
    <head>
        <x-portfolio.head :title="$title ?? null" />
    </head>

    <body data-hz-config="{{ json_encode($runtime) }}">
        @if ($intro ?? false)
            <div data-hz-intro class="hz-intro" hidden>
                <div class="hz-intro-inner">
                    <canvas data-hz-intro-canvas width="1040" height="1040" aria-hidden="true"></canvas>

                    <div class="hz-intro-meta">
                        <div class="hz-intro-kicker">
                            {{ __('portfolio.intro.kicker', ['year' => $site->profile_year]) }}
                        </div>
                        <div class="hz-intro-name">{{ Str::upper($site->profile_name) }}</div>
                        <div class="hz-intro-rule" aria-hidden="true"></div>
                        <div class="hz-intro-sub">{{ $site->t('hero_kicker') }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div data-hz-progress class="hz-progress" aria-hidden="true"></div>

        <canvas data-hz-bg class="hz-bg-field" aria-hidden="true"></canvas>

        {{ $slot }}

        <x-portfolio.command-palette />

        <div data-hz-lightbox class="hz-lightbox" hidden>
            <div data-hz-lightbox-inner class="hz-lightbox-inner">
                <img alt="">

                <button
                    type="button"
                    class="hz-lightbox-close"
                    data-hz-lightbox-close
                    aria-label="{{ __('portfolio.actions.close') }}"
                >
                    <x-portfolio.icon name="close" stroke-width="2.4" />
                </button>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
