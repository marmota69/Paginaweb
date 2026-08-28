<?php

/*
 | The portfolio hides the lightbox, the intro splash and the inactive theme
 | icon with the `hidden` attribute alone. That attribute is only styled by the
 | user-agent stylesheet, so any author `display` rule outranks it — which is
 | exactly what `.hz-lightbox { display: grid }` and `.btn svg { display: block }`
 | used to do, leaving the overlay painted over the whole page. These tests pin
 | the reset that makes `hidden` win.
 */

beforeEach(function () {
    $this->stylesheet = file_get_contents(dirname(__DIR__, 2).'/resources/css/portfolio.css');
});

test('the stylesheet neutralises the hidden attribute', function () {
    expect($this->stylesheet)->toMatch('/\[hidden\]\s*\{\s*display:\s*none\s*!important;\s*\}/');
});

test('elements the runtime toggles are styled with a display rule that hidden must beat', function () {
    // If these stop declaring `display`, the reset above is still harmless —
    // but while they do, it is the only thing keeping them off the page.
    expect($this->stylesheet)
        ->toContain('.hz-lightbox {')
        ->toContain('.hz-intro {')
        ->toContain('.btn svg {');
});
