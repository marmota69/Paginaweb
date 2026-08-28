<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Download;
use App\Models\Experience;
use App\Models\Guide;
use App\Models\PageView;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\SkillGroup;
use App\Models\SkillItem;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('the portfolio home page renders the published courses and guides', function () {
    $course = Course::factory()->create(['title' => 'React profesional']);
    $guide = Guide::factory()->create(['title' => 'Autenticación JWT']);
    $draftCourse = Course::factory()->unpublished()->create(['title' => 'Borrador de curso']);
    $draftGuide = Guide::factory()->unpublished()->create(['title' => 'Borrador de guía']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($course->title)
        ->assertSee($guide->title)
        ->assertDontSee($draftCourse->title)
        ->assertDontSee($draftGuide->title);
});

test('the home page renders every section anchor the navigation links to', function () {
    Project::factory()->create();
    Experience::factory()->create();
    SkillItem::factory()->create();

    $response = $this->get(route('home'))->assertOk();

    foreach (['inicio', 'sobre-mi', 'proyectos', 'experiencia', 'habilidades', 'cursos', 'guias', 'contacto'] as $anchor) {
        $response->assertSee('id="'.$anchor.'"', escape: false);
    }
});

test('a section with no content is left out of the page entirely', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('id="proyectos"', escape: false)
        ->assertDontSee('id="experiencia"', escape: false)
        ->assertDontSee('id="habilidades"', escape: false);
});

test('the home page renders the copy stored in the site settings', function () {
    SiteSetting::current()->update([
        'profile_name' => 'Ada Lovelace',
        'hero_title' => ['es' => 'Titular administrable', 'en' => 'Editable headline'],
        'about_title' => ['es' => 'Quién soy', 'en' => 'Who I am'],
        'footer_tagline' => ['es' => 'Pie editable', 'en' => 'Editable footer'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Titular administrable')
        ->assertSee('Quién soy')
        ->assertSee('Pie editable')
        ->assertSee('Ada Lovelace');
});

test('the home page renders the project, experience and skill records', function () {
    Project::factory()->create([
        'name' => 'FHIR Gateway',
        'type' => ['es' => 'API · Salud', 'en' => 'API · Health'],
        'stack' => ['Node.js', 'Redis'],
    ]);
    Experience::factory()->create([
        'company' => 'Nubetec SpA',
        'role' => ['es' => 'Ingeniera senior', 'en' => 'Senior engineer'],
    ]);
    $group = SkillGroup::factory()->create(['name' => ['es' => 'Lenguajes', 'en' => 'Languages']]);
    SkillItem::factory()->for($group, 'group')->create(['name' => 'TypeScript', 'percent' => 95]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('FHIR Gateway')
        ->assertSee('API · Salud')
        ->assertSee('Node.js')
        ->assertSee('Nubetec SpA')
        ->assertSee('Ingeniera senior')
        ->assertSee('Lenguajes')
        ->assertSee('TypeScript');
});

test('unpublished projects and experience stay off the public site', function () {
    Project::factory()->unpublished()->create(['name' => 'Proyecto oculto']);
    Experience::factory()->unpublished()->create(['company' => 'Empresa oculta']);
    Project::factory()->create(['name' => 'Proyecto visible']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Proyecto visible')
        ->assertDontSee('Proyecto oculto')
        ->assertDontSee('Empresa oculta');
});

test('the public site is ordered by the position the admin chose', function () {
    Project::factory()->create(['name' => 'Segundo', 'position' => 2]);
    Project::factory()->create(['name' => 'Primero', 'position' => 1]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Primero', 'Segundo']);
});

test('switching the language translates the database content', function () {
    SiteSetting::current()->update([
        'hero_title' => ['es' => 'Software claro', 'en' => 'Clear software'],
        'projects_title' => ['es' => 'Proyectos destacados', 'en' => 'Featured projects'],
    ]);
    Project::factory()->create([
        'name' => 'SIGA',
        'description' => ['es' => 'Sistema de matrícula', 'en' => 'Enrollment system'],
    ]);

    $this->get(route('home'))->assertOk()->assertSee('Software claro')->assertSee('Sistema de matrícula');

    $this->from(route('home'))
        ->get(route('locale.switch', 'en'))
        ->assertRedirect(route('home'));

    expect(session('locale'))->toBe('en');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Clear software')
        ->assertSee('Featured projects')
        ->assertSee('Enrollment system');
});

test('a half-translated field falls back instead of rendering blank', function () {
    SiteSetting::current()->update(['hero_title' => ['es' => 'Sólo en español', 'en' => '']]);

    session(['locale' => 'en']);

    $this->get(route('home'))->assertOk()->assertSee('Sólo en español');
});

test('the page describes itself with the stored seo tags', function () {
    SiteSetting::current()->update([
        'seo_title' => ['es' => 'Título SEO', 'en' => 'SEO title'],
        'seo_description' => ['es' => 'Descripción SEO', 'en' => 'SEO description'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Título SEO</title>', escape: false)
        ->assertSee('content="Descripción SEO"', escape: false);
});

test('an unsupported language is rejected', function () {
    $this->get(route('locale.switch', 'de'))->assertNotFound();

    expect(session('locale'))->toBeNull();
});

test('the contact form stores the message and notifies the owner', function () {
    Mail::fake();

    Livewire::test('pages::portfolio.home')
        ->set('name', 'Ana Pérez')
        ->set('email', 'ana@correo.com')
        ->set('subject', 'Consultoría')
        ->set('message', 'Quiero conversar sobre un proyecto.')
        ->call('submitContact')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSet('name', '')
        ->assertSee(__('portfolio.contact.ok_title'));

    $message = ContactMessage::query()->sole();

    expect($message->name)->toBe('Ana Pérez')
        ->and($message->email)->toBe('ana@correo.com')
        ->and($message->subject)->toBe('Consultoría')
        ->and($message->locale)->toBe('es')
        ->and($message->read_at)->toBeNull();

    Mail::assertSent(ContactMessageReceived::class);
});

test('the contact form validates its fields', function () {
    Mail::fake();

    Livewire::test('pages::portfolio.home')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('subject', '')
        ->set('message', '')
        ->call('submitContact')
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'email',
            'subject' => 'required',
            'message' => 'required',
        ])
        ->assertSet('sent', false);

    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('the confirmation dialog can be dismissed', function () {
    Livewire::test('pages::portfolio.home')
        ->set('sent', true)
        ->call('closeSent')
        ->assertSet('sent', false)
        ->assertDontSee(__('portfolio.contact.ok_title'));
});

test('a course page renders its details', function () {
    $course = Course::factory()->create([
        'title' => 'Docker y despliegue continuo',
        'description' => 'Contenedores y pipelines.',
        'duration' => '10 horas',
        'level' => 'avanzado',
    ]);

    $this->get(route('courses.show', $course))
        ->assertOk()
        ->assertSee('Docker y despliegue continuo')
        ->assertSee('Contenedores y pipelines.')
        ->assertSee('10 horas')
        ->assertSee(__('portfolio.levels.avanzado'));
});

test('an unpublished course is not reachable', function () {
    $course = Course::factory()->unpublished()->create();

    $this->get(route('courses.show', $course))->assertNotFound();
});

test('a guide page renders its markdown body as safe html', function () {
    $guide = Guide::factory()->create([
        'title' => 'Optimización en PostgreSQL',
        'content' => "Antes de cambiar de motor, lee el plan.\n## EXPLAIN ANALYZE\nBusca **Seq Scan** en tablas grandes.",
        'tags' => 'postgresql, sql',
    ]);

    $this->get(route('guides.show', $guide))
        ->assertOk()
        ->assertSee('Optimización en PostgreSQL')
        ->assertSee('<h3>EXPLAIN ANALYZE</h3>', escape: false)
        ->assertSee('<strong>Seq Scan</strong>', escape: false)
        ->assertSee('postgresql');
});

test('an unpublished guide is not reachable', function () {
    $guide = Guide::factory()->unpublished()->create();

    $this->get(route('guides.show', $guide))->assertNotFound();
});

test('reading a guide records a download', function () {
    $guide = Guide::factory()->create();

    $this->get(route('guides.show', $guide))->assertOk();

    expect($guide->downloads()->count())->toBe(1);
});

test('the course access link records a download and forwards to the platform', function () {
    $course = Course::factory()->create(['link' => 'https://cursos.example.test/react']);

    $this->get(route('courses.access', $course))
        ->assertRedirect('https://cursos.example.test/react');

    expect($course->downloads()->count())->toBe(1);
});

test('a course without a link sends the visitor back to the course page', function () {
    $course = Course::factory()->create(['link' => null]);

    $this->get(route('courses.access', $course))
        ->assertRedirect(route('courses.show', $course));

    expect(Download::query()->count())->toBe(1);
});

test('the course access link is closed for unpublished courses', function () {
    $course = Course::factory()->unpublished()->create(['link' => 'https://cursos.example.test/react']);

    $this->get(route('courses.access', $course))->assertNotFound();

    expect(Download::query()->count())->toBe(0);
});

test('a guest visit is recorded once per path and session', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('home'))->assertOk();

    expect(PageView::query()->where('path', '/')->count())->toBe(1);
});

test('visits from the authenticated owner are not recorded', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertOk();

    expect(PageView::query()->count())->toBe(0);
});

test('the navigation only links to sections the page actually renders', function () {
    // Nothing published: the three content-driven sections are not rendered,
    // so offering their anchors would give the visitor three dead buttons.
    $response = $this->get(route('home'))->assertOk();

    foreach (['#proyectos', '#experiencia', '#habilidades'] as $anchor) {
        $response->assertDontSee($anchor.'" data-hz-section-link', escape: false);
    }

    foreach (['#cursos', '#guias', '#contacto'] as $anchor) {
        $response->assertSee($anchor.'" data-hz-section-link', escape: false);
    }
});

test('publishing content brings its navigation link back', function () {
    Project::factory()->create();
    Experience::factory()->create();
    SkillItem::factory()->create();

    $response = $this->get(route('home'))->assertOk();

    foreach (['#proyectos', '#experiencia', '#habilidades'] as $anchor) {
        $response->assertSee($anchor.'" data-hz-section-link', escape: false);
    }
});

test('every navigation anchor resolves to a section on the page', function () {
    Project::factory()->create();

    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match_all('/href="[^"]*#([a-z-]+)" data-hz-section-link/', $html, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach (array_unique($matches[1]) as $anchor) {
        expect($html)->toContain('id="'.$anchor.'"');
    }
});

test('the hero call to action points at a section that exists', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match('/<a href="#([a-z-]+)" data-hz-section-link class="btn btn-primary"/', $html, $match);

    expect($match[1] ?? null)->not->toBeNull()
        ->and($html)->toContain('id="'.$match[1].'"');
});

test('the command palette only offers sections that exist', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match('/data-hz-items="([^"]*)"/', $html, $match);

    $items = json_decode(html_entity_decode($match[1] ?? '[]'), true);
    $anchors = collect($items)
        ->pluck('href')
        ->filter(fn (?string $href) => str_starts_with((string) $href, '#'))
        ->map(fn (string $href) => substr($href, 1));

    expect($anchors)->not->toBeEmpty();

    foreach ($anchors as $anchor) {
        expect($html)->toContain('id="'.$anchor.'"');
    }
});

test('the revealed sections survive a livewire update', function () {
    // The reveal class is added client-side by the IntersectionObserver, so a
    // morph that rewrote these elements' attributes would strip it and drop
    // every section back to opacity 0 — a blank page after closing the dialog.
    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match_all('/<section[^>]*data-hz-reveal[^>]*>/', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $tag) {
        expect($tag)->toContain('wire:ignore.self');
    }
});

test('dismissing the sent dialog leaves the page intact', function () {
    Mail::fake();

    Livewire::test('pages::portfolio.home')
        ->set('name', 'Ana Pérez')
        ->set('email', 'ana@correo.com')
        ->set('subject', 'Consultoría')
        ->set('message', 'Hola.')
        ->call('submitContact')
        ->assertSet('sent', true)
        ->call('closeSent')
        ->assertSet('sent', false)
        ->assertDontSee(__('portfolio.contact.ok_title'))
        // The sections are still rendered, so there is a page to come back to.
        ->assertSee('id="contacto"', escape: false)
        ->assertSee('id="sobre-mi"', escape: false);
});

test('the navigation offers the home and about links', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('#inicio" data-hz-section-link', escape: false)
        ->assertSee('#sobre-mi" data-hz-section-link', escape: false)
        ->assertSee(__('portfolio.nav.home'))
        ->assertSee(__('portfolio.nav.about'));
});
