<?php

use App\Models\Guide;
use App\Support\Markdown;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::portfolio')] class extends Component {
    public Guide $guide;

    /**
     * Resolve the guide from the slug and count the read as a download.
     */
    public function mount(Guide $guide): void
    {
        abort_unless($guide->is_published, 404);

        $this->guide = $guide;

        $guide->downloads()->create(['session_id' => request()->session()->getId()]);
    }

    /**
     * The guide body rendered from its Markdown subset.
     */
    #[Computed]
    public function body(): HtmlString
    {
        return app(Markdown::class)->toHtml($this->guide->content);
    }

    /**
     * Render the page with the guide title in the browser tab.
     */
    public function render(): View
    {
        return $this->view()->title($this->guide->title);
    }
}; ?>

@php
    $site = \App\Models\SiteSetting::current();
@endphp

<div>
    <div class="nav" style="position: sticky; top: 0; z-index: 50; background: var(--color-bg);">
        <div class="nav-brand" style="display: flex; align-items: center; gap: 10px;">
            <x-portfolio.globe />
            {{ $site->profile_initials }}
        </div>

        <a href="{{ route('home') }}#guias" class="btn btn-ghost" style="gap: 8px;" wire:navigate>
            <x-portfolio.icon name="arrow-left" size="14" />
            {{ __('portfolio.actions.back') }}
        </a>
    </div>

    <article class="hz-article">
        <div class="hz-article-meta">
            <span class="tag tag-accent">{{ $guide->category }}</span>
            <span class="text-muted" style="font-size: 13px;">
                {{ $guide->published_on?->translatedFormat('d F Y') }}
            </span>
        </div>

        <h1>{{ $guide->title }}</h1>

        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
            @foreach ($guide->tagList() as $tag)
                <span class="tag tag-neutral">{{ $tag }}</span>
            @endforeach
        </div>

        @if ($guide->imageUrl())
            <div class="grayscale" style="border: 2px solid var(--color-divider); margin: 24px 0;">
                <img
                    src="{{ $guide->imageUrl() }}"
                    alt="{{ __('portfolio.guides.image_alt', ['title' => $guide->title]) }}"
                    width="780"
                    height="440"
                    style="width: 100%; height: auto;"
                >
            </div>
        @endif

        <hr class="hr">

        <div class="hz-prose">{{ $this->body }}</div>
    </article>

    <footer class="hz-footer text-muted">
        <span>© {{ $site->profile_year }} {{ $site->profile_name }}</span>
        <span>{{ __('portfolio.footer') }}</span>
    </footer>
</div>
