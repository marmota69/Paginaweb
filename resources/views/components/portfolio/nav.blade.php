@php
    // Only the sections the page actually renders — see PortfolioSections.
    $sections = app(\App\Support\PortfolioSections::class)->navigation();
@endphp

<nav data-hz-nav class="nav hz-nav">
    <div class="hz-nav-spacer" aria-hidden="true"></div>

    <div class="hz-nav-links">
        @foreach ($sections as $anchor => $label)
            <a href="{{ route('home') }}#{{ $anchor }}" data-hz-section-link>{{ $label }}</a>
        @endforeach
    </div>

    <div class="hz-nav-tools">
        <button
            type="button"
            class="btn btn-icon btn-secondary"
            style="border: 0;"
            data-hz-cmdk-open
            aria-label="{{ __('portfolio.cmdk.title') }}"
            title="{{ __('portfolio.cmdk.title') }} (Ctrl+K)"
        >
            <x-portfolio.icon name="search" />
        </button>

        <div class="hz-lang">
            @foreach (config('portfolio.locales') as $locale)
                <a
                    href="{{ route('locale.switch', $locale) }}"
                    aria-current="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                    hreflang="{{ $locale }}"
                >{{ strtoupper($locale) }}</a>
            @endforeach
        </div>

        <button
            type="button"
            class="btn btn-icon btn-secondary"
            style="border: 0;"
            data-hz-theme-toggle
            aria-label="{{ __('portfolio.nav.theme') }}"
            title="{{ __('portfolio.nav.theme') }}"
        >
            <x-portfolio.icon name="sun" data-hz-icon="sun" hidden />
            <x-portfolio.icon name="moon" data-hz-icon="moon" />
        </button>

        <a
            href="{{ auth()->check() ? route('dashboard') : route('login') }}"
            class="btn btn-secondary"
            style="gap: 7px; height: 36px; padding-block: 0; border: 0;"
            wire:navigate
        >
            <x-portfolio.icon name="lock" size="13" />
            {{ __('portfolio.nav.admin') }}
        </a>
    </div>
</nav>
