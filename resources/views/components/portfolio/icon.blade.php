@props(['name', 'size' => 16])

@php
    $paths = [
        'sun' => '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="0"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'arrow-left' => '<path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path>',
        'arrow-right' => '<path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path>',
        'expand' => '<path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path>',
        'close' => '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
        'pencil' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>',
        'trash' => '<path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
        'check' => '<path d="M20 6 9 17l-5-5"></path>',
        'mail' => '<rect x="2" y="4" width="20" height="16"></rect><path d="m2 6 10 7 10-7"></path>',
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path>',
        'up' => '<path d="m18 15-6-6-6 6"></path>',
        'down' => '<path d="m6 9 6 6 6-6"></path>',
        'plus' => '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
    ];
@endphp

<svg
    {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}
>{!! $paths[$name] !!}</svg>
