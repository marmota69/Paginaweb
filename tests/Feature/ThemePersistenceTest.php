<?php

use App\Models\User;

/*
 | The theme is stored once, in localStorage under `flux.appearance`, and every
 | page resolves it before first paint.
 |
 | The resolver has to be Flux's own: its runtime ships on the public site too,
 | and when `Flux.applyAppearance` is missing, its Alpine effect *deletes* that
 | key — which silently wiped the visitor's choice on every reload and on every
 | language switch. So every layout must emit @fluxAppearance, and the mirror
 | script must only copy the class onto data-theme, never decide the theme.
 */

test('every authenticated page defines the appearance resolver', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertOk()
        ->assertSee('window.Flux = {', escape: false)
        ->assertSee('applyAppearance', escape: false)
        ->assertSee("localStorage.getItem('flux.appearance')", escape: false);
})->with([
    'dashboard.content',
    'dashboard.projects',
    'appearance.edit',
    'profile.edit',
]);

test('the public pages define the appearance resolver too', function () {
    // This is the page that regressed: it loads Flux's runtime for Livewire,
    // so without the resolver Flux erases the stored theme on every visit.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('window.Flux = {', escape: false)
        ->assertSee("localStorage.setItem('flux.appearance', 'dark')", escape: false);

    $this->get(route('login'))->assertOk()->assertSee('window.Flux = {', escape: false);
});

test('the mirror script copies the class onto data-theme without deciding it', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain("root.classList.contains('dark')")
        ->and($html)->toContain("root.setAttribute('data-theme'")
        // No second opinion on what the theme should be.
        ->and($html)->not->toContain('prefers-color-scheme: dark)\').matches ? ');
});

test('no layout hard-codes a theme that would override the stored choice', function () {
    $layouts = array_merge(
        glob(resource_path('views/layouts/*.blade.php')),
        glob(resource_path('views/layouts/*/*.blade.php')),
    );

    expect($layouts)->not->toBeEmpty();

    foreach ($layouts as $layout) {
        expect(file_get_contents($layout))->not->toContain('class="dark"');
    }
});

test('the portfolio toggle hands the choice to flux so it is persisted', function () {
    $runtime = file_get_contents(resource_path('js/portfolio.js'));

    expect($runtime)->toContain('window.Flux.appearance = theme')
        ->and($runtime)->toContain("classList.toggle('dark', theme === 'dark')");
});
