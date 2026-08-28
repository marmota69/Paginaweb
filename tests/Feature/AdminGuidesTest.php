<?php

use App\Models\Guide;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the guides admin', function () {
    auth()->logout();

    $this->get(route('dashboard.guides'))->assertRedirect(route('login'));
});

test('the guides admin lists every guide, drafts included', function () {
    $published = Guide::factory()->create(['title' => 'Autenticación JWT']);
    $draft = Guide::factory()->unpublished()->create(['title' => 'Borrador de guía']);

    $this->get(route('dashboard.guides'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertSee($draft->title)
        ->assertSee(__('portfolio.admin.new_guide'));
});

test('a guide can be created', function () {
    Livewire::test('pages::dashboard.guides')
        ->set('guideTitle', 'CI/CD con GitHub Actions')
        ->set('content', "Un pipeline no tiene que ser complejo.\n## El mínimo viable\n- Corre los tests")
        ->set('category', 'DevOps')
        ->set('tags', 'ci-cd, github, docker')
        ->set('publishedOn', '2026-01-08')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('guideTitle', '');

    $guide = Guide::query()->sole();

    expect($guide->title)->toBe('CI/CD con GitHub Actions')
        ->and($guide->slug)->toBe('cicd-con-github-actions')
        ->and($guide->category)->toBe('DevOps')
        ->and($guide->tagList())->toBe(['ci-cd', 'github', 'docker'])
        ->and($guide->published_on->format('Y-m-d'))->toBe('2026-01-08')
        ->and($guide->is_published)->toBeTrue();
});

test('a guide saved without a date is published today', function () {
    $this->travelTo('2026-07-26 10:00:00');

    Livewire::test('pages::dashboard.guides')
        ->set('guideTitle', 'Guía sin fecha')
        ->call('save')
        ->assertHasNoErrors();

    expect(Guide::query()->sole()->published_on->format('Y-m-d'))->toBe('2026-07-26');
});

test('creating a guide requires a title', function () {
    Livewire::test('pages::dashboard.guides')
        ->set('guideTitle', '')
        ->call('save')
        ->assertHasErrors(['guideTitle' => 'required']);

    expect(Guide::query()->count())->toBe(0);
});

test('a guide publication date must be a date', function () {
    Livewire::test('pages::dashboard.guides')
        ->set('guideTitle', 'Guía')
        ->set('publishedOn', 'ayer')
        ->call('save')
        ->assertHasErrors(['publishedOn' => 'date']);
});

test('a guide can be edited', function () {
    $guide = Guide::factory()->create([
        'title' => 'Título antiguo',
        'slug' => 'titulo-antiguo',
        'category' => 'Backend',
    ]);

    Livewire::test('pages::dashboard.guides')
        ->call('edit', $guide->id)
        ->assertSet('editing', $guide->id)
        ->assertSet('guideTitle', 'Título antiguo')
        ->assertSet('category', 'Backend')
        ->set('guideTitle', 'Título nuevo')
        ->set('category', 'Frontend')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', null);

    $guide->refresh();

    expect($guide->title)->toBe('Título nuevo')
        ->and($guide->slug)->toBe('titulo-nuevo')
        ->and($guide->category)->toBe('Frontend')
        ->and(Guide::query()->count())->toBe(1);
});

test('a guide can be hidden from the public site', function () {
    $guide = Guide::factory()->create();

    Livewire::test('pages::dashboard.guides')
        ->call('edit', $guide->id)
        ->set('isPublished', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($guide->refresh()->is_published)->toBeFalse();
});

test('a guide image can be uploaded and removed', function () {
    Storage::fake('public');

    $component = Livewire::test('pages::dashboard.guides')
        ->set('guideTitle', 'Guía con imagen')
        ->set('image', UploadedFile::fake()->image('portada.png'))
        ->call('save')
        ->assertHasNoErrors();

    $guide = Guide::query()->sole();
    $path = $guide->image_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $component->call('edit', $guide->id)
        ->assertSet('currentImage', $path)
        ->call('removeImage')
        ->assertSet('currentImage', null);

    expect($guide->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('a confirmed guide deletion removes the guide and its image', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->image('portada.jpg')->store('portfolio/guides', 'public');
    $guide = Guide::factory()->create(['image_path' => $path]);

    Livewire::test('pages::dashboard.guides')
        ->call('confirmDelete', $guide->id)
        ->assertSee(__('portfolio.admin.confirm_title'))
        ->call('delete')
        ->assertSet('deleting', null);

    expect(Guide::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('a guide deletion can be cancelled', function () {
    $guide = Guide::factory()->create();

    Livewire::test('pages::dashboard.guides')
        ->call('confirmDelete', $guide->id)
        ->call('cancelDelete')
        ->assertSet('deleting', null);

    expect(Guide::query()->count())->toBe(1);
});
