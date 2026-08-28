@props(['variant'])

{{-- A three-scene canvas with the dot indicator the design pairs it with. --}}
<div data-hz-scene {{ $attributes->class('hz-scene') }}>
    <canvas
        @if ($variant === 'about') data-hz-about-scenes @else data-hz-exp-scenes @endif
        width="840"
        height="840"
        aria-hidden="true"
    ></canvas>

    <div class="hz-scene-dots">
        @foreach (range(0, 2) as $index)
            <span data-hz-dot @class(['is-active' => $index === 0])></span>
        @endforeach
    </div>
</div>
