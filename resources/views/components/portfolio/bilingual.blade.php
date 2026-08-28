@props([
    'label',
    'model',
    'rows' => null,
    'hint' => null,
])

{{--
    Two inputs side by side, one per language, bound to `$model.es` / `$model.en`.
    The wire:key is derived from the model path so Livewire never carries a value
    from one field over to another when the tab changes.
--}}
<div class="field hz-bilingual" wire:key="bilingual-{{ Str::slug($model) }}">
    <span class="hz-bilingual-label">{{ $label }}</span>

    <div class="hz-bilingual-grid">
        @foreach (config('portfolio.locales') as $locale)
            <div>
                <label class="hz-bilingual-locale" for="{{ Str::slug($model) }}-{{ $locale }}">
                    {{ strtoupper($locale) }}
                </label>

                @if ($rows)
                    <textarea
                        id="{{ Str::slug($model) }}-{{ $locale }}"
                        class="input"
                        rows="{{ $rows }}"
                        wire:model="{{ $model }}.{{ $locale }}"
                        autocomplete="off"
                    ></textarea>
                @else
                    <input
                        id="{{ Str::slug($model) }}-{{ $locale }}"
                        class="input"
                        type="text"
                        wire:model="{{ $model }}.{{ $locale }}"
                        autocomplete="off"
                    >
                @endif

                @error($model.'.'.$locale) <div class="field-error">{{ $message }}</div> @enderror
            </div>
        @endforeach
    </div>

    @if ($hint)
        <div class="hz-field-hint text-muted">{{ $hint }}</div>
    @endif
</div>
