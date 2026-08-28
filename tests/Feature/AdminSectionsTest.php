<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillGroup;
use App\Models\SkillItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the section editors', function () {
    auth()->logout();

    $this->get(route('dashboard.projects'))->assertRedirect(route('login'));
    $this->get(route('dashboard.experiences'))->assertRedirect(route('login'));
    $this->get(route('dashboard.skills'))->assertRedirect(route('login'));
});

/* ── projects ─────────────────────────────────────────────────────────── */

test('a project can be created with bilingual copy', function () {
    Livewire::test('pages::dashboard.projects')
        ->set('projectName', 'FHIR Gateway')
        ->set('year', '2024')
        ->set('type.es', 'API · Salud')
        ->set('type.en', 'API · Health')
        ->set('description.es', 'Capa de interoperabilidad clínica.')
        ->set('description.en', 'Clinical interoperability layer.')
        ->set('stack', 'Node.js, FHIR, Redis')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('projectName', '');

    $project = Project::query()->sole();

    expect($project->name)->toBe('FHIR Gateway')
        ->and($project->t('type', 'es'))->toBe('API · Salud')
        ->and($project->t('type', 'en'))->toBe('API · Health')
        ->and($project->stackList())->toBe(['Node.js', 'FHIR', 'Redis'])
        ->and($project->is_published)->toBeTrue();
});

test('creating a project requires a name and a valid link', function () {
    Livewire::test('pages::dashboard.projects')
        ->set('projectName', '')
        ->set('url', 'no-es-una-url')
        ->call('save')
        ->assertHasErrors(['projectName' => 'required', 'url' => 'url']);

    expect(Project::query()->count())->toBe(0);
});

test('a project can be edited and hidden', function () {
    $project = Project::factory()->create(['name' => 'Antiguo']);

    Livewire::test('pages::dashboard.projects')
        ->call('edit', $project->id)
        ->assertSet('projectName', 'Antiguo')
        ->set('projectName', 'Nuevo')
        ->set('isPublished', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', null);

    $project->refresh();

    expect($project->name)->toBe('Nuevo')
        ->and($project->is_published)->toBeFalse()
        ->and(Project::query()->count())->toBe(1);
});

test('projects can be reordered', function () {
    $first = Project::factory()->create(['name' => 'Primero', 'position' => 1]);
    $second = Project::factory()->create(['name' => 'Segundo', 'position' => 2]);

    Livewire::test('pages::dashboard.projects')->call('move', $second->id, -1);

    expect(Project::query()->ordered()->pluck('name')->all())->toBe(['Segundo', 'Primero']);

    Livewire::test('pages::dashboard.projects')->call('move', $second->id, 1);

    expect(Project::query()->ordered()->pluck('name')->all())->toBe(['Primero', 'Segundo'])
        ->and($first->fresh())->not->toBeNull();
});

test('moving the first project up does nothing', function () {
    Project::factory()->create(['name' => 'Primero', 'position' => 1]);
    Project::factory()->create(['name' => 'Segundo', 'position' => 2]);

    $first = Project::query()->ordered()->first();

    Livewire::test('pages::dashboard.projects')->call('move', $first->id, -1);

    expect(Project::query()->ordered()->pluck('name')->all())->toBe(['Primero', 'Segundo']);
});

test('a new project is appended to the end of the rail', function () {
    Project::factory()->create(['name' => 'Existente', 'position' => 5]);

    Livewire::test('pages::dashboard.projects')
        ->set('projectName', 'Nuevo')
        ->call('save')
        ->assertHasNoErrors();

    expect(Project::query()->ordered()->pluck('name')->all())->toBe(['Existente', 'Nuevo']);
});

test('a confirmed project deletion removes it', function () {
    $project = Project::factory()->create(['name' => 'Descartado']);

    Livewire::test('pages::dashboard.projects')
        ->call('confirmDelete', $project->id)
        ->assertSee(__('portfolio.admin.confirm_title'))
        ->call('delete')
        ->assertSet('deleting', null);

    expect(Project::query()->count())->toBe(0);
});

/* ── experience ───────────────────────────────────────────────────────── */

test('an experience can be created and marked as current', function () {
    Livewire::test('pages::dashboard.experiences')
        ->set('periodFrom', '2021')
        ->set('periodTo', '')
        ->set('company', 'Nubetec SpA')
        ->set('role.es', 'Ingeniera senior')
        ->set('role.en', 'Senior engineer')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $experience = Experience::query()->sole();

    expect($experience->period_to)->toBeNull()
        ->and($experience->isCurrent())->toBeTrue()
        ->and($experience->period())->toBe('2021')
        ->and($experience->t('role', 'en'))->toBe('Senior engineer');
});

test('an experience with an end date shows a range', function () {
    Livewire::test('pages::dashboard.experiences')
        ->set('periodFrom', '2018')
        ->set('periodTo', '2021')
        ->set('role.es', 'Desarrollador')
        ->call('save')
        ->assertHasNoErrors();

    expect(Experience::query()->sole()->period())->toBe('2018 — 2021');
});

test('creating an experience requires a start date', function () {
    Livewire::test('pages::dashboard.experiences')
        ->set('periodFrom', '')
        ->call('save')
        ->assertHasErrors(['periodFrom' => 'required']);

    expect(Experience::query()->count())->toBe(0);
});

test('experience entries can be reordered and deleted', function () {
    Experience::factory()->create(['company' => 'Primera', 'position' => 1]);
    $second = Experience::factory()->create(['company' => 'Segunda', 'position' => 2]);

    Livewire::test('pages::dashboard.experiences')->call('move', $second->id, -1);

    expect(Experience::query()->ordered()->pluck('company')->all())->toBe(['Segunda', 'Primera']);

    Livewire::test('pages::dashboard.experiences')
        ->call('confirmDelete', $second->id)
        ->call('delete');

    expect(Experience::query()->count())->toBe(1);
});

/* ── skills ───────────────────────────────────────────────────────────── */

test('a skill group and its skills can be created', function () {
    $component = Livewire::test('pages::dashboard.skills')->call('addGroup');

    $group = SkillGroup::query()->sole();

    $component->call('addItem', $group->id)
        ->set("groupNames.{$group->id}.es", 'Lenguajes')
        ->set("groupNames.{$group->id}.en", 'Languages');

    $item = SkillItem::query()->sole();

    $component->set("items.{$item->id}.name", 'TypeScript')
        ->set("items.{$item->id}.percent", 95)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($group->fresh()->t('name', 'en'))->toBe('Languages')
        ->and($item->fresh()->name)->toBe('TypeScript')
        ->and($item->fresh()->percent)->toBe(95);
});

test('a skill percentage must be between 0 and 100', function () {
    $group = SkillGroup::factory()->create();
    $item = SkillItem::factory()->for($group, 'group')->create();

    Livewire::test('pages::dashboard.skills')
        ->set("items.{$item->id}.name", 'PHP')
        ->set("items.{$item->id}.percent", 150)
        ->call('save')
        ->assertHasErrors(["items.{$item->id}.percent" => 'max']);
});

test('skills are reordered inside their own group', function () {
    $groupA = SkillGroup::factory()->create(['position' => 1]);
    $groupB = SkillGroup::factory()->create(['position' => 2]);

    SkillItem::factory()->for($groupA, 'group')->create(['name' => 'A1', 'position' => 1]);
    $a2 = SkillItem::factory()->for($groupA, 'group')->create(['name' => 'A2', 'position' => 2]);
    SkillItem::factory()->for($groupB, 'group')->create(['name' => 'B1', 'position' => 1]);

    Livewire::test('pages::dashboard.skills')->call('moveItem', $a2->id, -1);

    expect($groupA->fresh()->items->pluck('name')->all())->toBe(['A2', 'A1'])
        ->and($groupB->fresh()->items->pluck('name')->all())->toBe(['B1']);
});

test('skill groups can be reordered', function () {
    SkillGroup::factory()->create(['name' => ['es' => 'Uno', 'en' => 'One'], 'position' => 1]);
    $second = SkillGroup::factory()->create(['name' => ['es' => 'Dos', 'en' => 'Two'], 'position' => 2]);

    Livewire::test('pages::dashboard.skills')->call('moveGroup', $second->id, -1);

    expect(SkillGroup::query()->ordered()->get()->map(fn (SkillGroup $g) => $g->t('name', 'es'))->all())
        ->toBe(['Dos', 'Uno']);
});

test('deleting a group removes its skills too', function () {
    $group = SkillGroup::factory()->create();
    SkillItem::factory()->count(3)->for($group, 'group')->create();

    Livewire::test('pages::dashboard.skills')
        ->call('confirmDeleteGroup', $group->id)
        ->assertSee(__('portfolio.admin.confirm_title'))
        ->call('deleteGroup')
        ->assertSet('deletingGroup', null);

    expect(SkillGroup::query()->count())->toBe(0)
        ->and(SkillItem::query()->count())->toBe(0);
});

test('a single skill can be deleted without touching its group', function () {
    $group = SkillGroup::factory()->create();
    $item = SkillItem::factory()->for($group, 'group')->create();
    SkillItem::factory()->for($group, 'group')->create();

    Livewire::test('pages::dashboard.skills')->call('deleteItem', $item->id);

    expect(SkillItem::query()->count())->toBe(1)
        ->and(SkillGroup::query()->count())->toBe(1);
});
