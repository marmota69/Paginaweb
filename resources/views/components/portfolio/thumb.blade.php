@props(['url' => null, 'alt' => ''])

{{-- Course / guide cover with the zoom button that opens the lightbox. --}}
<div class="hz-row-figure grayscale">
    @if ($url)
        <img src="{{ $url }}" alt="{{ $alt }}" width="210" height="140" loading="lazy">

        <button
            type="button"
            class="hz-zoom"
            data-hz-zoom="{{ $url }}"
            title="{{ __('portfolio.actions.zoom') }}"
            aria-label="{{ __('portfolio.actions.zoom') }}"
        >
            <x-portfolio.icon name="expand" size="14" />
        </button>
    @else
        <div class="hz-placeholder">{{ $alt }}</div>
    @endif
</div>
