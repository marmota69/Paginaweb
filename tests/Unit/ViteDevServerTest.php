<?php

/*
 | Left to itself Vite binds only to [::1]. It then writes that address into
 | public/hot, so a browser on http://127.0.0.1 is told to fetch the bundles
 | from a host it cannot reach — the scripts fail silently and every control
 | that needs JavaScript stops responding, while plain links keep working.
 |
 | Pinning the host is the whole fix, so pin it here too.
 */

beforeEach(function () {
    $this->config = file_get_contents(dirname(__DIR__, 2).'/vite.config.js');
});

test('the dev server binds to an explicit ipv4 host', function () {
    expect($this->config)->toMatch("/host:\s*'127\.0\.0\.1'/");
});

test('the dev server still allows cross-origin requests from the app', function () {
    expect($this->config)->toMatch('/cors:\s*true/');
});

test('the portfolio bundles are registered as build inputs', function () {
    expect($this->config)
        ->toContain('resources/css/portfolio.css')
        ->toContain('resources/js/portfolio.js');
});
