<?php

use App\CourseLevel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the courses admin', function () {
    auth()->logout();

    $this->get(route('dashboard.courses'))->assertRedirect(route('login'));
});

test('the courses admin lists every course, drafts included', function () {
    $published = Course::factory()->create(['title' => 'React profesional']);
    $draft = Course::factory()->unpublished()->create(['title' => 'Borrador de curso']);

    $this->get(route('dashboard.courses'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertSee($draft->title)
        ->assertSee(__('portfolio.admin.new_course'));
});

test('a course can be created', function () {
    Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', 'APIs REST con Node.js')
        ->set('description', 'Endpoints, middleware y despliegue.')
        ->set('level', 'intermedio')
        ->set('duration', '16 horas')
        ->set('link', 'https://cursos.example.test/node')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('courseTitle', '');

    $course = Course::query()->sole();

    expect($course->title)->toBe('APIs REST con Node.js')
        ->and($course->slug)->toBe('apis-rest-con-nodejs')
        ->and($course->level)->toBe(CourseLevel::Intermedio)
        ->and($course->duration)->toBe('16 horas')
        ->and($course->is_published)->toBeTrue();
});

test('creating a course requires a title', function () {
    Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', '')
        ->call('save')
        ->assertHasErrors(['courseTitle' => 'required']);

    expect(Course::query()->count())->toBe(0);
});

test('a course link must be a url', function () {
    Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', 'Curso')
        ->set('link', 'no-es-una-url')
        ->call('save')
        ->assertHasErrors(['link' => 'url']);
});

test('two courses with the same title get distinct slugs', function () {
    Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', 'Curso repetido')
        ->call('save')
        ->set('courseTitle', 'Curso repetido')
        ->call('save')
        ->assertHasNoErrors();

    expect(Course::query()->orderBy('id')->pluck('slug')->all())
        ->toBe(['curso-repetido', 'curso-repetido-2']);
});

test('a course can be edited', function () {
    $course = Course::factory()->create([
        'title' => 'Título antiguo',
        'slug' => 'titulo-antiguo',
        'level' => CourseLevel::Basico,
    ]);

    Livewire::test('pages::dashboard.courses')
        ->call('edit', $course->id)
        ->assertSet('editing', $course->id)
        ->assertSet('courseTitle', 'Título antiguo')
        ->assertSet('level', 'basico')
        ->set('courseTitle', 'Título nuevo')
        ->set('level', 'avanzado')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', null);

    $course->refresh();

    expect($course->title)->toBe('Título nuevo')
        ->and($course->slug)->toBe('titulo-nuevo')
        ->and($course->level)->toBe(CourseLevel::Avanzado)
        ->and(Course::query()->count())->toBe(1);
});

test('editing can be cancelled without saving', function () {
    $course = Course::factory()->create(['title' => 'Sin cambios']);

    Livewire::test('pages::dashboard.courses')
        ->call('edit', $course->id)
        ->set('courseTitle', 'Cambio descartado')
        ->call('cancel')
        ->assertSet('editing', null)
        ->assertSet('courseTitle', '');

    expect($course->refresh()->title)->toBe('Sin cambios');
});

test('a course can be hidden from the public site', function () {
    $course = Course::factory()->create();

    Livewire::test('pages::dashboard.courses')
        ->call('edit', $course->id)
        ->set('isPublished', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($course->refresh()->is_published)->toBeFalse();
});

test('a course image can be uploaded and removed', function () {
    Storage::fake('public');

    $component = Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', 'Curso con imagen')
        ->set('image', UploadedFile::fake()->image('portada.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $course = Course::query()->sole();

    expect($course->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($course->image_path);

    $path = $course->image_path;

    $component->call('edit', $course->id)
        ->assertSet('currentImage', $path)
        ->call('removeImage')
        ->assertSet('currentImage', null);

    expect($course->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('an uploaded course image must be an image', function () {
    Storage::fake('public');

    Livewire::test('pages::dashboard.courses')
        ->set('courseTitle', 'Curso')
        ->set('image', UploadedFile::fake()->create('notas.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['image' => 'image']);

    expect(Course::query()->count())->toBe(0);
});

test('deleting a course asks for confirmation first', function () {
    $course = Course::factory()->create(['title' => 'Curso a borrar']);

    Livewire::test('pages::dashboard.courses')
        ->call('confirmDelete', $course->id)
        ->assertSet('deleting', $course->id)
        ->assertSee(__('portfolio.admin.confirm_title'))
        ->call('cancelDelete')
        ->assertSet('deleting', null);

    expect(Course::query()->count())->toBe(1);
});

test('a confirmed course deletion removes the course and its image', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->image('portada.jpg')->store('portfolio/courses', 'public');
    $course = Course::factory()->create(['image_path' => $path]);

    Livewire::test('pages::dashboard.courses')
        ->call('confirmDelete', $course->id)
        ->call('delete')
        ->assertSet('deleting', null);

    expect(Course::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});
