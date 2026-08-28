<?php

use App\Models\Course;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::portfolio')] class extends Component {
    public Course $course;

    /**
     * Resolve the course from the slug, hiding drafts from visitors.
     */
    public function mount(Course $course): void
    {
        abort_unless($course->is_published, 404);

        $this->course = $course;
    }

    /**
     * Render the page with the course title in the browser tab.
     */
    public function render(): View
    {
        return $this->view()->title($this->course->title);
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

        <a href="{{ route('home') }}#cursos" class="btn btn-ghost" style="gap: 8px;" wire:navigate>
            <x-portfolio.icon name="arrow-left" size="14" />
            {{ __('portfolio.actions.back') }}
        </a>
    </div>

    <article class="hz-article">
        <div class="hz-article-meta">
            <span class="{{ $course->level->tagClass() }}">{{ $course->level->label() }}</span>
            <span class="text-muted" style="font-size: 13px;">{{ $course->duration }}</span>
        </div>

        <h1>{{ $course->title }}</h1>
        <p class="hz-article-lead">{{ $course->description }}</p>

        @if ($course->imageUrl())
            <div class="grayscale" style="border: 2px solid var(--color-divider); margin: 24px 0;">
                <img
                    src="{{ $course->imageUrl() }}"
                    alt="{{ __('portfolio.courses.image_alt', ['title' => $course->title]) }}"
                    width="780"
                    height="440"
                    style="width: 100%; height: auto;"
                >
            </div>
        @endif

        <hr class="hr">

        <div class="hz-facts">
            <div class="hz-fact">
                <div class="hz-fact-label text-muted">{{ __('portfolio.admin.f_level') }}</div>
                <div class="hz-fact-value">{{ $course->level->label() }}</div>
            </div>
            <div class="hz-fact">
                <div class="hz-fact-label text-muted">{{ __('portfolio.admin.f_duration') }}</div>
                <div class="hz-fact-value">{{ $course->duration }}</div>
            </div>
            <div class="hz-fact">
                <div class="hz-fact-label text-muted">{{ __('portfolio.courses.language') }}</div>
                <div class="hz-fact-value">{{ __('portfolio.courses.language_value') }}</div>
            </div>
        </div>

        <div class="hz-article-ctas">
            @if ($course->link)
                <a href="{{ route('courses.access', $course) }}" class="btn btn-primary" rel="noopener">
                    {{ __('portfolio.courses.go') }}
                </a>
            @endif

            <a href="{{ route('home') }}#contacto" class="btn {{ $course->link ? 'btn-secondary' : 'btn-primary' }}" wire:navigate>
                {{ __('portfolio.courses.enroll') }}
            </a>

            <a href="{{ route('home') }}#cursos" class="btn btn-secondary" wire:navigate>
                {{ __('portfolio.courses.more') }}
            </a>
        </div>
    </article>

    <footer class="hz-footer text-muted">
        <span>© {{ $site->profile_year }} {{ $site->profile_name }}</span>
        <span>{{ __('portfolio.footer') }}</span>
    </footer>
</div>
