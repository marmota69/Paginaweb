@php
    use App\Models\Course;
    use App\Models\Guide;

    $items = collect(app(\App\Support\PortfolioSections::class)->all())
        ->map(fn (string $label, string $anchor): array => [
            'group' => __('portfolio.cmdk.navigate'),
            'label' => $label,
            'href' => '#'.$anchor,
        ])
        ->values();

    $items = $items
        ->concat(Course::query()->published()->ordered()->get()->map(fn (Course $course): array => [
            'group' => __('portfolio.nav.courses'),
            'label' => $course->title,
            'href' => route('courses.show', $course),
        ]))
        ->concat(Guide::query()->published()->ordered()->get()->map(fn (Guide $guide): array => [
            'group' => __('portfolio.nav.guides'),
            'label' => $guide->title,
            'href' => route('guides.show', $guide),
        ]))
        ->push([
            'group' => __('portfolio.cmdk.actions'),
            'label' => __('portfolio.nav.theme'),
            'action' => 'theme',
        ])
        ->push([
            'group' => __('portfolio.cmdk.actions'),
            'label' => __('portfolio.cmdk.admin'),
            'href' => auth()->check() ? route('dashboard') : route('login'),
        ]);

    foreach (config('portfolio.locales') as $locale) {
        $items->push([
            'group' => __('portfolio.cmdk.actions'),
            'label' => __('portfolio.cmdk.language', ['locale' => strtoupper($locale)]),
            'href' => route('locale.switch', $locale),
        ]);
    }
@endphp

<div
    data-hz-cmdk
    data-hz-items="{{ $items->toJson() }}"
    data-hz-empty="{{ __('portfolio.cmdk.empty') }}"
    class="hz-cmdk"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('portfolio.cmdk.title') }}"
    hidden
>
    <div class="hz-cmdk-panel">
        <input
            data-hz-cmdk-input
            class="hz-cmdk-input"
            type="text"
            placeholder="{{ __('portfolio.cmdk.placeholder') }}"
            aria-label="{{ __('portfolio.cmdk.placeholder') }}"
            autocomplete="off"
        >

        <div data-hz-cmdk-list class="hz-cmdk-list"></div>

        <div class="hz-cmdk-hint">
            <span><span class="hz-kbd">↑</span> <span class="hz-kbd">↓</span> {{ __('portfolio.cmdk.move') }}</span>
            <span><span class="hz-kbd">↵</span> {{ __('portfolio.cmdk.open') }}</span>
            <span><span class="hz-kbd">esc</span> {{ __('portfolio.actions.close') }}</span>
        </div>
    </div>
</div>
