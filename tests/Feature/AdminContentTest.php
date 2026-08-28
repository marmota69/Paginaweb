<?php

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Identity is required to save, so give a fresh settings row the minimum.
    SiteSetting::current()->update([
        'profile_name' => 'Héctor Zamorano',
        'profile_initials' => 'HZ',
        'contact_email' => 'hola@example.test',
    ]);
});

test('guests cannot reach the content editor', function () {
    auth()->logout();

    $this->get(route('dashboard.content'))->assertRedirect(route('login'));
});

test('the editor loads the stored settings into its fields', function () {
    SiteSetting::current()->update([
        'profile_name' => 'Ada Lovelace',
        'profile_initials' => 'AL',
        'contact_email' => 'ada@example.test',
        'hero_title' => ['es' => 'Titular', 'en' => 'Headline'],
    ]);

    Livewire::test('pages::dashboard.content')
        ->assertSet('profileName', 'Ada Lovelace')
        ->assertSet('profileInitials', 'AL')
        ->assertSet('contactEmail', 'ada@example.test')
        ->assertSet('text.hero_title.es', 'Titular')
        ->assertSet('text.hero_title.en', 'Headline');
});

test('editing the hero changes what the public site shows', function () {
    Livewire::test('pages::dashboard.content')
        ->set('text.hero_title.es', 'Nuevo titular')
        ->set('text.hero_title.en', 'New headline')
        ->set('text.hero_lead.es', 'Nueva bajada')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Nuevo titular')
        ->assertSee('Nueva bajada');

    session(['locale' => 'en']);
    $this->get(route('home'))->assertOk()->assertSee('New headline');
});

test('the editor saves every tab in one write', function () {
    Livewire::test('pages::dashboard.content')
        ->set('profileName', 'Ada Lovelace')
        ->set('profileInitials', 'AL')
        ->set('profileYear', 2030)
        ->set('contactEmail', 'ada@example.test')
        ->set('githubUrl', 'https://github.com/ada')
        ->set('githubLabel', '@ada')
        ->set('text.about_title.es', 'Quién soy')
        ->set('text.contact_location.es', 'Londres')
        ->set('text.footer_tagline.es', 'Hecho en Londres')
        ->set('text.seo_title.es', 'Ada — Portafolio')
        ->call('save')
        ->assertHasNoErrors();

    $settings = SiteSetting::query()->sole();

    expect($settings->profile_name)->toBe('Ada Lovelace')
        ->and($settings->profile_initials)->toBe('AL')
        ->and($settings->profile_year)->toBe(2030)
        ->and($settings->contact_email)->toBe('ada@example.test')
        ->and($settings->github_url)->toBe('https://github.com/ada')
        ->and($settings->t('about_title', 'es'))->toBe('Quién soy')
        ->and($settings->t('contact_location', 'es'))->toBe('Londres')
        ->and($settings->t('footer_tagline', 'es'))->toBe('Hecho en Londres')
        ->and($settings->t('seo_title', 'es'))->toBe('Ada — Portafolio');
});

test('the editor validates the identity and contact fields', function () {
    Livewire::test('pages::dashboard.content')
        ->set('profileName', '')
        ->set('contactEmail', 'no-es-un-correo')
        ->set('githubUrl', 'tampoco')
        ->call('save')
        ->assertHasErrors([
            'profileName' => 'required',
            'contactEmail' => 'email',
            'githubUrl' => 'url',
        ]);
});

test('switching tabs keeps the values that were not saved yet', function () {
    Livewire::test('pages::dashboard.content')
        ->set('text.hero_title.es', 'Borrador')
        ->call('showTab', 'about')
        ->assertSet('tab', 'about')
        ->assertSet('text.hero_title.es', 'Borrador');
});

test('an unknown tab falls back to the first one', function () {
    Livewire::test('pages::dashboard.content')
        ->call('showTab', 'no-existe')
        ->assertSet('tab', 'identity');
});

test('the portrait can be uploaded and removed', function () {
    Storage::fake('public');

    $component = Livewire::test('pages::dashboard.content')
        ->set('photo', UploadedFile::fake()->image('retrato.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $path = SiteSetting::query()->sole()->photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->get(route('home'))->assertOk()->assertSee($path, escape: false);

    $component->call('removePhoto');

    expect(SiteSetting::query()->sole()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('the hero falls back to the initials when there is no portrait', function () {
    SiteSetting::current()->update(['photo_path' => null, 'profile_initials' => 'AL']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<div class="hz-placeholder">AL</div>', escape: false);
});

test('an uploaded portrait must be an image', function () {
    Storage::fake('public');

    Livewire::test('pages::dashboard.content')
        ->set('photo', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['photo' => 'image']);
});

test('the sharing image can be uploaded and removed', function () {
    Storage::fake('public');

    $component = Livewire::test('pages::dashboard.content')
        ->set('seoImage', UploadedFile::fake()->image('og.png'))
        ->call('save')
        ->assertHasNoErrors();

    $path = SiteSetting::query()->sole()->seo_image_path;

    expect($path)->not->toBeNull();
    $this->get(route('home'))->assertOk()->assertSee('og:image', escape: false);

    $component->call('removeSeoImage');

    expect(SiteSetting::query()->sole()->seo_image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
